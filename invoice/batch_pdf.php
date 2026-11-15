<?php

class invoice_batch_pdf
{
    public static function merge($pdf, array $files, string $destination): void
    {
        if (!$files) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['there_are_no_invoices_to_combine']));
        foreach ($files as $file) {
            if (!is_file($file) || !is_readable($file)) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['an_invoice_pdf_is_missing_the_combined_document_could_not_be_created']));
        }
        $pdf->SetDisplayMode('fullpage');
        foreach ($files as $file) {
            $pages = $pdf->setSourceFile($file);
            if ($pages < 1) throw new RuntimeException(($GLOBALS['LANG']['MODULE']['invoice']['ui']['an_invoice_pdf_has_no_pages']));
            for ($page = 1; $page <= $pages; $page++) {
                $template = $pdf->importPage($page);
                $pdf->AddPage();
                // Import the complete page, including the original header and footer.
                $pdf->useTemplate($template, 0, 0, null, null, true);
            }
        }
        $pdf->Output($destination, 'F');
    }
}
