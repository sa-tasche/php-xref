// Copyright © 2021 Sara Tasche <mitgedanken>
//
// Licensed under the ISC License;
// you may not use this file except in compliance with the License.

<?php

$includeDir = ("@php_dir@" == "@"."php_dir@") ? dirname(__FILE__) . "/.." : "@php_dir@/XRef";
require_once "$includeDir/XRef.class.php";

class CITest extends PHPUnit_Framework_TestCase {
    protected $xref;
    const SMARTY_CLASS_PATH = '/Users/igariev/dev/Smarty-2.6.27/libs/Smarty.class.php';

    public function __construct() {
        // don't read config file, if any
        XRef::setConfigFileName("default");
        XRef::setConfigValue("git.repository-dir", ".");
        $this->xref = new XRef();
        $this->xref->loadPluginGroup('lint');
    }

    public function setUp() {
        $has_git = false;
        if (file_exists(".git")) {
            exec("git status 2>&1 ", $ouptut, $retval);
            if ($retval == 0) {
                $has_git = true;
            }
        }

        if (!$has_git) {
            $this->markTestSkipped("No git found");
        }

        if (! file_exists(self::SMARTY_CLASS_PATH)) {
            $this->markTestSkipped("No Smarty found");
        }
    }

    public function testCI() {
        $old_rev        = "377d2edc1da549a80b5b44286e7dcaf59cee300a";
        $current_rev    = "190fc6a9fddcae0313decbbbce92e4a83bf47ab9";

        XRef::setConfigValue('mail.reply-to',       'no-reply@xref-lint.net');
        XRef::setConfigValue('mail.from',           'ci-server@xref-lint.net');
        XRef::setConfigValue('mail.to',             array('test@xref-lint.net', '{%ae}', '{%an}@xref-lint.net'));
        XRef::setConfigValue('project.name',        'unit-test');
        XRef::setConfigValue('project.source-url',  'https://github.com/gariev/xref/blob/{%revision}/{%fileName}#L{%lineNumber}');

        XRef::setConfigValue('xref.smarty-class',   self::SMARTY_CLASS_PATH);
        XRef::setConfigValue('xref.data-dir',       'tmp');

        $scm = $this->xref->getSourceCodeManager();
        $file_provider_old = $scm->getFileProvider($old_rev);
        $file_provider_new = $scm->getFileProvider($current_rev);
        $modified_files = $scm->getListOfModifiedFiles($old_rev, $current_rev);
        $lint_engine = new XRef_LintEngine_ProjectCheck($this->xref, false);
        $errors = $lint_engine->getIncrementalReport($file_provider_old, $file_provider_new, $modified_files);
        list ($recipients, $subject, $body, $headers) = $this->xref->getNotificationEmail($errors, 'tests-git', $old_rev, $current_rev);

        //print_r(array($recipients, $subject, $body, $headers));

        // assert that our comparison function works
        $this->assertTrue( $this->strSmartSpaces("\t hello,\r \n world  ") == $this->strSmartSpaces("hello, world") );
        $this->assertTrue( $this->strSmartSpaces("\t hello,\r \n world  ") != $this->strSmartSpaces("hello , world") );

        $this->assertTrue( count($recipients) == 3 );
        $this->assertTrue( $recipients[0] == 'test@xref-lint.net' );
        $this->assertTrue( $recipients[1] == 'gariev@hotmail.com' );
        $this->assertTrue( $recipients[2] == 'gariev@xref-lint.net' );

        $this->assertTrue( $this->strSmartSpaces($subject) == "XRef CI unit-test: tests-git/190fc6a");

        $expected_body = <<<END
            <html><body>
            Hi, you've got this e-mail as the author (gariev) of commit 190fc6a to branch tests-git.
            It looks like there are problems in file(s) modified since previous revision 377d2ed:
                <ul>
                    <li>broken.php</li>
                    <ul>
                        <li>
                            <span class='error'>error</span>
                            (<a href="https://github.com/gariev/xref/blob/master/README.md#xr010">xr010</a>): Use of unknown variable (\$error)
                             at <a href="https://github.com/gariev/xref/blob/190fc6a9fddcae0313decbbbce92e4a83bf47ab9/broken.php#L3">line 3</a>
                        </li>
                    </ul>
                </ul>
            <p>If the problems above are real, you can fix them.
            If these warnings are from code merged from other branch and not result of merge conflict, you can ignore them or find the author of the original commit.
            If the report is wrong, you can help <a href='mailto:gariev@hotmail.com?subject=xref'>improve XRef CI</a> itself and/or ignore this e-mail.
            </p>
            <p><small>
              Generated by XRef CI version <<VERSION>>. About XRef:
                <a href="https://github.com/gariev/xref/blob/master/README.md">documentation</a>,
                <a href="https://github.com/gariev/xref/">source code</a>,
                <a href="http://xref-lint.net/bin/xref-lint.php">online tool</a>.
            </small></p>
            </body></html>
END;

        $expected_body = str_replace('<<VERSION>>', XRef::version(), $expected_body);
        $this->assertTrue( $this->strSmartSpaces($body) == $this->strSmartSpaces($expected_body) );

        $expected_headers = <<<END
            MIME-Version: 1.0
            Content-type: text/html
            Reply-to: no-reply@xref-lint.net
            From: ci-server@xref-lint.net
END;
        $this->assertTrue( $this->strSmartSpaces($headers) == $this->strSmartSpaces($expected_headers) );
    }

    protected function strSmartSpaces($str) {
        return trim(preg_replace('#[\\s\\n\\r\\t]+#', ' ', $str));
    }

}



