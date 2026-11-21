<?php

namespace App\Modules\Dev\Traits;

use XLSXWriter;

trait ExportTrait
{
    /**
     * 下载xlsx
     *
     * @return void
     *
     * @author wxyClark
     *
     * @create 2025/11/30 18:20
     *
     * @version 1.0
     */
    public function xlsxToDown(XLSXWriter $writer, $fileName)
    {
        header('Content-disposition: attachment; filename="'.XLSXWriter::sanitize_filename($fileName).'"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        $writer->writeToStdOut();
    }
}
