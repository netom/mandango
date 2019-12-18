<?php

/*
 * This file is part of Mandango.
 *
 * (c) Fábián Tamás László <giganetom@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mandango\Extension;

use Mandango\Mondator\Definition\Method;
use Mandango\Mondator\Extension;
use Mandango\Twig\Mandango as MandangoTwig;


/**
 * DocumentInitializer extension.
 *
 * @author Fábián Tamás László <giganetom@gmail.com>
 */
class DocumentInitializer extends Extension
{
    /**
     * {@inheritdoc}
     */
    protected function doClassProcess()
    {
        $this->processTemplate($this->definitions['document_base'], file_get_contents(__DIR__.'/templates/DocumentInitializer.php.twig'));
    }

    protected function configureTwig(\Twig\Environment $twig)
    {
        $twig->addExtension(new MandangoTwig());
    }

}
