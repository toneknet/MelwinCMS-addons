// Messages are supplied by the CMS-selected PHP language file.
function invoiceText(key) {
    var element = document.getElementById('invoice-language');
    var messages = element ? JSON.parse(element.textContent) : {};
    return messages[key] || key;
}
function invoiceHtml(key) {
    return invoiceText(key).replace(/[&<>"']/g, function (character) {
        return {'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'}[character];
    });
}
document.addEventListener('DOMContentLoaded', function () {
    setupInvoiceEditor();
    document.addEventListener('click', function (event) {
        if (event.target.closest('.invoice-unpay') && !window.confirm((invoiceText("mark_the_invoice_as_unpaid")))) {
            event.preventDefault();
            return;
        }
        var open = event.target.closest('.invoice-payment-open');
        if (open) {
            var dialog = document.getElementById(open.getAttribute('data-dialog'));
            if (dialog) dialog.showModal();
        }
        var cancel = event.target.closest('.invoice-payment-cancel');
        if (cancel) cancel.closest('dialog').close();
    });
    function filterInvoices() {
        var start = document.getElementById('start_date');
        var end = document.getElementById('end_date');
        if (!start || !end) return;
        var url = new URL(window.location.href);
        url.searchParams.set('start_date', start.value);
        url.searchParams.set('end_date', end.value);
        var search = document.getElementById('search');
        if (search && search.value) url.searchParams.set('search', search.value);
        else url.searchParams.delete('search');
        url.searchParams.delete('page');
        window.location.assign(url.toString());
    }
    ['start_date','end_date'].forEach(function (id) {
        var input = document.getElementById(id);
        if (input) input.addEventListener('change', filterInvoices);
    });
    var search = document.getElementById('search');
    if (search) search.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') { event.preventDefault(); filterInvoices(); }
    });
});

function setupInvoiceEditor() {
    var form = document.querySelector('.invoice-create');
    if (!form) return;
    var customer = form.querySelector('#invoice-customer');
    var customerId = form.querySelector('#invoice-customer-id');
    var body = form.querySelector('.invoice-create-rows tbody');
    var error = form.querySelector('.invoice-editor-error');
    var nextIndex = 0;
    var format = new Intl.NumberFormat(invoiceText('locale'), {minimumFractionDigits: 2, maximumFractionDigits: 2});

    function autocomplete(input, list, type, selected, label) {
        var items = [], sequence = 0, timer;
        function choose() {
            var item = items.find(function (entry) { return label(entry) === input.value; });
            if (item) { selected(item); return true; }
            return false;
        }
        function search() {
            var current = ++sequence;
            var root = typeof URLroot === 'string' ? URLroot : '/';
            var url = new URL(root.replace(/\/?$/, '/') + 'system/ajax/ajaxRequest.php', window.location.href);
            url.searchParams.set('action', 'invoice');
            url.searchParams.set('type', 'lookup_' + type);
            url.searchParams.set('term', input.value);
            fetch(url.toString(), {credentials: 'same-origin'}).then(function (response) {
                if (!response.ok) throw Error();
                return response.json();
            }).then(function (result) {
                if (current !== sequence) return;
                if (!result.success) throw Error();
                items = result.items || [];
                list.replaceChildren();
                items.forEach(function (item) {
                    var option = document.createElement('option');
                    option.value = label(item);
                    list.appendChild(option);
                });
                error.textContent = '';
                if (type === 'customers') form.querySelector('.invoice-lookup-status').textContent = items.length ? (invoiceText("select_a_customer_from_the_suggestions")) : (invoiceText("no_customers_were_found_in_the_current_company"));
            }).catch(function () {
                if (current === sequence) error.textContent = (invoiceText("search_failed_check_the_invoice_ajax_registration_and_try_again"));
            });
        }
        input.addEventListener('input', function () {
            ++sequence; clearTimeout(timer);
            if (choose()) return;
            selected(null);
            timer = setTimeout(search, 180);
        });
        input.addEventListener('change', choose);
        input.addEventListener('focus', function () { if (!input.value) search(); });
    }
    autocomplete(customer, form.querySelector('#invoice-customer-options'), 'customers', function (item) {
        customerId.value = item ? item.id : '';
        if (item) form.querySelector('.invoice-lookup-status').textContent = (invoiceText("selected_customer")) + item.namn;
    }, function (item) { return item.namn + ' — ' + (item.adress || item.postort || '') + ' (#' + item.id + ')'; });

    function number(value) { return Number(String(value).replace(/\s/g, '').replace(',', '.')); }
    function totals() {
        var sum = 0, automatic = false;
        body.querySelectorAll('.invoice-product-row').forEach(function (row) {
            var amount = row.querySelector('.product_amount').value;
            var output = row.querySelector('.invoice-row-total');
            if (amount === 'x') { automatic = true; output.textContent = (invoiceText("per_customer")); return; }
            var total = number(amount) * number(row.querySelector('.product_price').value) * (1 + number(row.querySelector('.product_tax').value) / 100);
            if (!Number.isFinite(total)) { output.textContent = '—'; return; }
            sum += total; output.textContent = format.format(total);
        });
        form.querySelector('.invoice-create-total').textContent = automatic ? (invoiceText("calculated_per_customer")) : format.format(Math.sign(sum) * Math.round(Math.abs(sum)));
    }

    function addRow(textOnly, initial) {
        var index = nextIndex++;
        var row = document.createElement('tr');
        var prefix = 'db_invoice[' + index + ']';
        if (textOnly) {
            row.innerHTML = '<td colspan="6"><textarea name="' + prefix + ("[product_text]\" aria-label=\"" + invoiceHtml("text_line") + "\" placeholder=\"" + invoiceHtml("invoice_text") + "\"></textarea><input type=\"hidden\" name=\"") + prefix + '[product_amount]" value="0"><input type="hidden" name="' + prefix + '[product_price]" value="0"></td>';
        } else {
            row.className = 'invoice-product-row';
            row.innerHTML = '<td><input class="product_name" name="' + prefix + '[product_name]" list="invoice-products-' + index + ("\" autocomplete=\"off\" placeholder=\"" + invoiceHtml("search_for_a_product_or_enter_a_service") + "\" aria-label=\"" + invoiceHtml("product_or_service") + "\" required><datalist id=\"invoice-products-") + index + '"></datalist>'
                + '<input type="hidden" class="product_id" name="' + prefix + '[product_id]"><input type="hidden" class="product_connected" name="' + prefix + '[product_connected]">'
                + '<textarea name="' + prefix + ("[product_text]\" aria-label=\"" + invoiceHtml("description") + "\" placeholder=\"" + invoiceHtml("additional_description_optional") + "\"></textarea></td>")
                + '<td><input class="product_amount" name="' + prefix + ("[product_amount]\" value=\"1\" aria-label=\"" + invoiceHtml("quantity") + "\" required></td>")
                + '<td><input class="product_unit" name="' + prefix + ("[product_unit]\" aria-label=\"" + invoiceHtml("unit") + "\"></td>")
                + '<td><input class="product_price" name="' + prefix + ("[product_price]\" value=\"0\" aria-label=\"" + invoiceHtml("price_excluding_vat") + "\" inputmode=\"decimal\" required></td>")
                + '<td><select class="product_tax" name="' + prefix + ("[product_tax]\" aria-label=\"" + invoiceHtml("vat_rate") + "\"><option value=\"0\">0 %</option><option value=\"6\">6 %</option><option value=\"12\">12 %</option><option value=\"25\">25 %</option></select></td>")
                + '<td class="invoice-row-total">0,00</td>';
            autocomplete(row.querySelector('.product_name'), row.querySelector('datalist'), 'products', function (item) {
                row.querySelector('.product_id').value = item ? item.id : '';
                row.querySelector('.product_connected').value = item ? item.connected || '' : '';
                if (item) {
                    row.querySelector('.product_name').value = item.label;
                    row.querySelector('.product_unit').value = item.unit || '';
                    row.querySelector('.product_price').value = item.price || '0';
                    row.querySelector('.product_tax').value = String(item.tax || 0);
                }
                totals();
            }, function (item) { return item.label + ' (#' + item.id + ')'; });
        }
        var remove = document.createElement('td');
        var button = document.createElement('button');
        button.type = 'button'; button.textContent = (invoiceText("remove")); button.className = 'invoice-remove-row';
        button.addEventListener('click', function () { row.remove(); totals(); });
        remove.appendChild(button); row.appendChild(remove); body.appendChild(row);
        if (initial) {
            Object.keys(initial).forEach(function (key) {
                var field = row.querySelector('[name="' + prefix + '[' + key + ']"]');
                if (field) field.value = initial[key];
            });
        }
        totals();
    }
    form.querySelector('.invoice-add-product').addEventListener('click', function () { addRow(false); });
    form.querySelector('.invoice-add-text').addEventListener('click', function () { addRow(true); });
    body.addEventListener('input', totals);
    body.addEventListener('change', totals);
    var initialRows = JSON.parse(form.getAttribute('data-initial-rows') || '[]');
    if (initialRows.length) initialRows.forEach(function (row) { addRow(!row.product_name, row); });
    else addRow(false);

    var checks = form.querySelectorAll('[name="db_select_all_user[]"]');
    function multiple() {
        var count = Array.from(checks).filter(function (check) { return check.checked; }).length;
        var status = form.querySelector('.invoice-multiple-status');
        status.hidden = count === 0; status.textContent = count + (invoiceText("customers_selected_one_invoice_is_created_per_customer"));
        customer.disabled = count > 0; customer.required = count === 0;
    }
    checks.forEach(function (check) { check.addEventListener('change', multiple); });
    form.querySelector('#invoice-select-all').addEventListener('change', function (event) {
        checks.forEach(function (check) { check.checked = event.target.checked; }); multiple();
    });
    multiple();
    function dueDate() {
        var date = new Date(form.querySelector('#invoice-date').value + 'T12:00:00Z');
        var days = Number(form.querySelector('#invoice-terms').value);
        if (!Number.isFinite(date.getTime()) || !Number.isFinite(days)) return;
        date.setUTCDate(date.getUTCDate() + days);
        form.querySelector('#invoice-due').value = date.toISOString().slice(0, 10);
    }
    form.querySelector('#invoice-date').addEventListener('change', dueDate);
    form.querySelector('#invoice-terms').addEventListener('change', dueDate);
    form.addEventListener('submit', function (event) {
        error.textContent = '';
        var any = Array.from(checks).some(function (check) { return check.checked; });
        if ((!any && !customerId.value) || !body.querySelector('.invoice-product-row')) {
            event.preventDefault(); error.textContent = (invoiceText("select_a_customer_from_the_suggestions_or_select_multiple_customers_"));
        }
    });
}
