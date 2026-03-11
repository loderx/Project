<?php
function simpleOfferPdf(array $offer, string $studioName): string {
    $text = "PhotoCalendar Offer\nStudio: {$studioName}\nTitle: {$offer['title']}\nTotal: {$offer['total_amount']} BGN\nValid until: {$offer['valid_until']}\n\n{$offer['body']}";
    $content = str_replace(["\r", "\n"], ['','\\n'], $text);
    $stream = "BT /F1 11 Tf 50 770 Td ({$content}) Tj ET";
    $len = strlen($stream);
    return "%PDF-1.1\n1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj\n4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n5 0 obj << /Length {$len} >> stream\n{$stream}\nendstream endobj\nxref\n0 6\n0000000000 65535 f \n0000000010 00000 n \n0000000060 00000 n \n0000000117 00000 n \n0000000243 00000 n \n0000000313 00000 n \ntrailer << /Root 1 0 R /Size 6 >>\nstartxref\n{$len}\n%%EOF";
}
