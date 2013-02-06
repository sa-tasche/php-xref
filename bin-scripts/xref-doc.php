<?php
/**
 * bin/xref-doc.php
 *
 * This is a script to create cross-reference docs of source code.
 *
 * @author Igor Gariev <gariev@hotmail.com>
 * @copyright Copyright (c) 2013 Igor Gariev
 * @licence http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

$includeDir = ("@php_dir@" == "@"."php_dir@") ? dirname(__FILE__) . "/.." : "@php_dir@/Xref";
require_once("$includeDir/XRef.class.php");

// command-line arguments
list ($options, $arguments) = XRef::getCmdOptions();

//help
if (XRef::needHelp() || count($arguments)) {
    XRef::showHelpScreen("xref-doc - tool to create cross-reference source code reports");
    exit(1);
}

$xref = new XRef();
foreach (XRef::getConfigValue("doc.source-code-dir") as $path) {
    $xref->addPath($path);
}
$xref->removeStartingPath( XRef::getConfigValue("doc.remove-path", '') );
$xref->setOutputDir( XRef::getConfigValue("doc.output-dir") );

$xref->loadPluginGroup("doc");
$plugins = $xref->getPlugins("XRef_IDocumentationPlugin");
$numberOfFiles = 0;
$numberOfCodeLines = 0;

// 1. Call each plugin once for each input file
foreach ($xref->getFiles() as $filename => $ext) {
    try {
        $pf = $xref->getParsedFile($filename, $ext);
        foreach ($plugins as $pluginId => $plugin) {
            $plugin->generateFileReport($pf);
        }
        $numberOfFiles++;
        $numberOfCodeLines += $pf->getNumberOfLines();
        $pf->release(); // help PHP garbage collector to free memory
    } catch(Exception $e) {
        error_log("Can't process file '$filename': " . $e->getMessage() . "\n");
    }
}

// 2. Notify each plugin that all files are done
foreach ($plugins as $pluginId => $plugin) {
    $plugin->generateTotalReport();
}

// 3. Create index page
$reports = array(); // report name --> report url
foreach ($plugins as $pluginId => $plugin) {
    $reports = array_merge($reports, $plugin->getReportLink());
}
ksort($reports);

list($fh) = $xref->getOutputFileHandle("index", null);
fwrite($fh,
    $xref->fillTemplate(
        "doc-index.tmpl",
        array(
            'reports'       => $reports,
            'date'          => date(DATE_RFC850),
            'numberOfFiles' => $numberOfFiles,
            'numberOfCodeLines' => $numberOfCodeLines,
        )
    )
);
fclose($fh);

// vim: tabstop=4 expandtab
