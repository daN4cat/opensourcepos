<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * PDF helper
 */

function create_pdf($html, $filename = '')
{
    $options = new Dompdf\Options();
    $options->setIsRemoteEnabled(FALSE);
    $options->setIsPhpEnabled(FALSE);
    $options->setChroot(FCPATH);

    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->loadHtml(str_replace(array("\n", "\r"), '', $html));
    $dompdf->render();
    
    if($filename != '')
    {
        $dompdf->stream($filename . '.pdf');
    }
    else
    {
        return $dompdf->output();
    }
}
?>
