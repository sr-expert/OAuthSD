<?php

// define('PHPED_UNITTEST_LISTENER_VERSION', '17.0.17006');

class PhpED_phpUnitListener_exception extends Exception {};

class PhpED_phpUnitListener extends PHPUnit_Util_Printer implements PHPUnit_Framework_TestListener {
    protected $currentTestName = '';
    protected $currentTestPass = NULL;
    protected $TestSuite = array();
    
    public function __construct($out) {
        if (!extension_loaded("dbg"))
            throw new PhpED_phpUnitListener_exception("dbg extension is not loaded");
        if (version_compare(phpversion("dbg"), "6.2.0") < 0)
            throw new PhpED_phpUnitListener_exception("installed dbg extesion is older than 6.2, please update to 6.2 or higher");
        $this->out = dbg_openpipe($out);
        $this->outTarget = $out;
    }
    public function __destructor() {
        closetest();
        if ($this->fout) {
            for ($i = count($TestSuite) - 1; $i >= 0; $i--) {
                $this->write(
                  array(
                    'event' => 'suiteEnd',
                    'suite' => $TestSuite[$i]
                  )
                );
            }
        }
        @fflush($this->fout); 
        @fclose($this->fout);
        $this->fout = NULL;
    }
    
    private function bt(Exception $e) {
        return PHPUnit_Util_Filter::getFilteredStacktrace($e, FALSE, TRUE);
    }
    
    private function closetest() {
        if (!empty($this->currentTestName)) {
            $this->write(
                array(
                    'event' => 'testEnd',
                    'test' => $this->currentTestName
                )
            );
        }
        $this->currentTestName = '';
        $this->currentTestPass = NULL;
    }
    
    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {
        $outOfSeq = empty($this->currentTestName);
        if ($outOfSeq)
            $this->startTest($test);
        $this->writeCase(
          'error',
          $time,
          $this->bt($e),
          PHPUnit_Framework_TestFailure::exceptionToString($e)
        );
        $this->currentTestPass = FALSE;
        if ($outOfSeq)
            $this->endTest($test, 0);
    }
    
    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
        $outOfSeq = empty($this->currentTestName);
        if ($outOfSeq)
            $this->startTest($test);
        $this->writeCase(
            'fail',
            $time,
            $this->bt($e),
            PHPUnit_Framework_TestFailure::exceptionToString($e)
        );
        $this->currentTestPass = FALSE;
        if ($outOfSeq)
            $this->endTest($test, 0);
    }
    
    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
        $outOfSeq = empty($this->currentTestName);
        if ($outOfSeq)
            $this->startTest($test);
        $this->writeCase(
            'incomplete',
            $time, 
            $this->bt($e), 
            PHPUnit_Framework_TestFailure::exceptionToString($e)
        );

        $this->currentTestPass = FALSE;
        if ($outOfSeq)
            $this->endTest($test, 0);
    }
    
    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
        $outOfSeq = empty($this->currentTestName);
        if ($outOfSeq)
            $this->startTest($test);
        $this->writeCase(
            'skipped', 
            $time, 
            $this->bt($e), 
            PHPUnit_Framework_TestFailure::exceptionToString($e)
        );
        $this->currentTestPass = FALSE;
        if ($outOfSeq)
            $this->endTest($test, 0);
    }

    public function addRiskyTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
        $outOfSeq = empty($this->currentTestName);
        if ($outOfSeq)
            $this->startTest($test);
        $this->writeCase(
            'risky', 
            $time, 
            $this->bt($e), 
            PHPUnit_Framework_TestFailure::exceptionToString($e)
        );
        $this->currentTestPass = FALSE;
        if ($outOfSeq)
            $this->endTest($test, 0);
    }

    
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
        $Name = $suite->getName();
        if (empty($Name))
            $Name = '<noname>';
            
        $this->closetest();
        array_push($this->TestSuite, $Name);

        $this->write(
            array(
                'event' => 'suiteStart',
                'suite' => $Name,
            )
        );
    }
    
    public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
        $this->closetest();
        $Name = array_pop($this->TestSuite);
        $this->write(
            array(
                'event' => 'suiteEnd',
                'suite' => $Name,
            )
        );
    }
    
    public function startTest(PHPUnit_Framework_Test $test) {
        $buffer = sprintf('%s::%s', get_class($test), $test->getName(FALSE));

        $this->closetest();
        $this->currentTestName = $buffer;
        $this->currentTestPass = TRUE;
                      
        $this->write(
            array(
                'event' => 'testStart',
                'test'  => $this->currentTestName,
                'data'  => $test->getName(TRUE)
            )
        );
    }
    
    public function endTest(PHPUnit_Framework_Test $test, $time) {
        if ($this->currentTestPass === TRUE) {
            $this->writeCase(
                'pass', 
                $time
            );
        } else if ($this->currentTestPass !== FALSE) {
            $this->writeCase(
                'bogus',
                $time
            );
        }
        $this->closetest();
    }
    
    protected function writeCase($status, $time, $trace = '', $message = '') {
        $a = array(
            'event'   => 'test',
            'test'    => $this->currentTestName,
            'status'  => $status,
            'time'    => $time,
        );
        if ($trace)
            $a['trace'] = $trace;
        if ($message)
            $a['message'] = $message;
        $this->write($a);
    }
    
    public function write($buffer) {
        if ($this->out !== FALSE)
            parent::write(json_encode($buffer));
    }
}

?>