<?php
/**
 * SimplePdf — a minimal, dependency-free PDF writer.
 *
 * Produces PDF 1.4 documents using only the standard (non-embedded)
 * Helvetica / Helvetica-Bold fonts, filled/stroked rectangles and
 * word-wrapped text. No Composer packages, no network access, no
 * external binaries — just plain PHP, which is all this project uses.
 */
class SimplePdf {
    private $pageWidth = 595.28;
    private $pageHeight = 841.89;
    public $marginLeft = 50;
    public $marginRight = 50;
    public $marginTop = 50;
    public $marginBottom = 60;

    private $objects = [];
    private $pageContents = [];
    private $curContent = '';
    private $x = 0;
    private $y = 0;
    private $fontStyle = '';
    private $fontSize = 11;
    private $fillColor = [0, 0, 0];
    private $textColor = [0, 0, 0];
    private $drawColor = [0, 0, 0];
    private $pageNum = 0;
    private $pageDecorator = null;

    public function __construct() {
        $this->addPage();
    }

    // Optional callable(SimplePdf $pdf, int $pageNumber) invoked at the
    // top of every page (including the first) — used to stamp a
    // letterhead header/footer on each page automatically.
    public function setPageDecorator(callable $decorator) {
        $this->pageDecorator = $decorator;
        ($this->pageDecorator)($this, $this->pageNum ?: 1);
    }

    public function addPage() {
        if ($this->curContent !== '') {
            $this->pageContents[] = $this->curContent;
        }
        $this->curContent = '';
        $this->x = $this->marginLeft;
        $this->y = $this->marginTop;
        $this->pageNum++;
        if ($this->pageDecorator) {
            ($this->pageDecorator)($this, $this->pageNum);
        }
    }

    public function pageContentHeight() {
        return $this->pageHeight - $this->marginTop - $this->marginBottom;
    }

    public function getY() {
        return $this->y;
    }

    public function setY($y) {
        $this->y = $y;
    }

    public function setFont($style = '', $size = 11) {
        $this->fontStyle = $style;
        $this->fontSize = $size;
    }

    public function setFillColor($r, $g, $b) {
        $this->fillColor = [$r, $g, $b];
    }

    public function setTextColor($r, $g, $b) {
        $this->textColor = [$r, $g, $b];
    }

    public function setDrawColor($r, $g, $b) {
        $this->drawColor = [$r, $g, $b];
    }

    // Checks whether $needed points of vertical space remain on the
    // current page; starts a new page if not.
    public function checkPageBreak($needed) {
        if ($this->y + $needed > $this->pageHeight - $this->marginBottom) {
            $this->addPage();
            return true;
        }
        return false;
    }

    // Full-bleed filled rectangle in page coordinates (from the top).
    public function rectTopDown($x, $y, $w, $h, $style = 'F') {
        $pdfY = $this->pageHeight - $y - $h;
        $col = $this->fillColor;
        $this->curContent .= sprintf("%.3F %.3F %.3F rg\n", $col[0] / 255, $col[1] / 255, $col[2] / 255);
        $op = $style === 'S' ? 'S' : ($style === 'FD' ? 'B' : 'f');
        $this->curContent .= sprintf("%.2F %.2F %.2F %.2F re %s\n", $x, $pdfY, $w, $h, $op);
    }

    public function line($x1, $y1, $x2, $y2) {
        $col = $this->drawColor;
        $this->curContent .= sprintf("%.3F %.3F %.3F RG\n", $col[0] / 255, $col[1] / 255, $col[2] / 255);
        $this->curContent .= sprintf("%.2F %.2F m %.2F %.2F l S\n", $x1, $this->pageHeight - $y1, $x2, $this->pageHeight - $y2);
    }

    private function pdfFontName() {
        return $this->fontStyle === 'B' ? 'F2' : 'F1';
    }

    private function escapeText($s) {
        // Approximate WinAnsi (CP1252) so accented characters degrade
        // gracefully instead of corrupting the PDF byte stream.
        $s = @mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
        if ($s === false) {
            $s = preg_replace('/[^\x20-\x7E]/', '', (string)$s);
        }
        $s = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
        return $s;
    }

    // Writes a single line of text at top-down coordinates ($x,$y is the
    // top-left of the text baseline area).
    public function text($x, $y, $str) {
        $col = $this->textColor;
        $pdfY = $this->pageHeight - $y - $this->fontSize * 0.85;
        $this->curContent .= sprintf("BT %.3F %.3F %.3F rg /%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n",
            $col[0] / 255, $col[1] / 255, $col[2] / 255,
            $this->pdfFontName(), $this->fontSize, $x, $pdfY, $this->escapeText($str));
    }

    // Approximate Helvetica character width as a fraction of font size.
    private function charWidth($ch) {
        if ($ch === ' ') return 0.278;
        if (strpos('iIl.,\'":;!|', $ch) !== false) return 0.28;
        if (strpos('mMWw', $ch) !== false) return 0.83;
        if (ctype_upper($ch) || ctype_digit($ch)) return 0.63;
        return 0.5;
    }

    private function stringWidth($str) {
        $w = 0;
        $len = mb_strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $w += $this->charWidth(mb_substr($str, $i, 1));
        }
        return $w * $this->fontSize;
    }

    // Word-wraps $text within $width points, writing each line and
    // advancing $this->y by $lineHeight per line. Handles page breaks and
    // blank lines (paragraph breaks) in the source text.
    public function writeParagraph($text, $width, $lineHeight = 15) {
        $paragraphs = explode("\n", str_replace("\r\n", "\n", (string)$text));
        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para === '') {
                $this->y += $lineHeight * 0.6;
                continue;
            }
            $words = preg_split('/\s+/', $para);
            $line = '';
            foreach ($words as $word) {
                $test = $line === '' ? $word : $line . ' ' . $word;
                if ($this->stringWidth($test) > $width && $line !== '') {
                    $this->checkPageBreak($lineHeight);
                    $this->text($this->x, $this->y, $line);
                    $this->y += $lineHeight;
                    $line = $word;
                } else {
                    $line = $test;
                }
            }
            if ($line !== '') {
                $this->checkPageBreak($lineHeight);
                $this->text($this->x, $this->y, $line);
                $this->y += $lineHeight;
            }
        }
    }

    public function output($filename = 'document.pdf') {
        if ($this->curContent !== '') {
            $this->pageContents[] = $this->curContent;
            $this->curContent = '';
        }
        $pageCount = count($this->pageContents) ?: 1;

        $objects = [];
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";

        $kids = [];
        $firstPageObjNum = 4; // objects 1=Catalog 2=Pages 3=Font1 ... (fonts placed before pages)
        $fontObjNum1 = 3;
        $fontObjNum2 = 4;
        $pageStart = 5;
        $contentStart = $pageStart + $pageCount;

        for ($i = 0; $i < $pageCount; $i++) {
            $kids[] = ($pageStart + $i) . ' 0 R';
        }
        $objects[2] = "<< /Type /Pages /Kids [" . implode(' ', $kids) . "] /Count $pageCount /MediaBox [0 0 {$this->pageWidth} {$this->pageHeight}] /Resources << /Font << /F1 {$fontObjNum1} 0 R /F2 {$fontObjNum2} 0 R >> >> >>";

        $objects[$fontObjNum1] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[$fontObjNum2] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        for ($i = 0; $i < $pageCount; $i++) {
            $pageObjNum = $pageStart + $i;
            $contentObjNum = $contentStart + $i;
            $objects[$pageObjNum] = "<< /Type /Page /Parent 2 0 R /Contents {$contentObjNum} 0 R >>";
            $stream = $this->pageContents[$i];
            $objects[$contentObjNum] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}endstream";
        }

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }

        $xrefStart = strlen($pdf);
        $maxNum = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($maxNum + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($n = 1; $n <= $maxNum; $n++) {
            if (isset($offsets[$n])) {
                $pdf .= sprintf("%010d 00000 n \n", $offsets[$n]);
            } else {
                $pdf .= "0000000000 00000 f \n";
            }
        }
        $pdf .= "trailer\n<< /Size " . ($maxNum + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefStart}\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        header('X-Content-Type-Options: nosniff');
        echo $pdf;
    }
}
