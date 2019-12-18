<?php
/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */
namespace airmoi\FileMaker\Command;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\Object\Result;
use airmoi\FileMaker\Parser\DataApiResult;

/**
 * Command class that performs a ScriptMaker script.
 * Create this command with {@link FileMaker::newPerformScriptCommand()}.
 *
 * @package FileMaker
 */
class PerformScript extends Command
{
    protected $skip;
    protected $max;

    protected $script;
    protected $scriptParams;

    /**
     * PerformScript command constructor.
     *
     * @ignore
     * @param \airmoi\FileMaker\FileMaker $fm FileMaker object the command was created by.
     * @param string $layout Layout to use for script context.
     * @param string $scriptName Name of the script to run.
     * @param string $scriptParameters Any parameters to pass to the script.
     */
    public function __construct(FileMaker $fm, $layout, $scriptName, $scriptParameters = null)
    {
        parent::__construct($fm, $layout);
        $this->script       = $scriptName;
        $this->scriptParams = $scriptParameters;
    }

    /**
     * Sets a range to request only part of the result set.
     *
     * @param integer $skip Number of records to skip past. Default is 0.
     * @param integer $max Maximum number of records to return.
     *        Default is all.
     *
     * @return self
     */
    public function setRange($skip = 0, $max = null)
    {
        $this->skip = $skip;
        $this->max  = $max;
        return $this;
    }

    /**
     * Returns the current range settings.
     *
     * @return array Associative array with two keys: 'skip' for
     * the current skip setting, and 'max' for the current maximum
     * number of records. If either key does not have a value, the
     * returned value for that key is NULL.
     */
    public function getRange()
    {
        return [
            'skip' => $this->skip,
            'max'  => $this->max
        ];
    }

    /**
     *
     * @return Result
     */
    public function execute($result = null)
    {
        $params             = $this->getCommandParams();
        if ($this->fm->engine == "cwp") {
            $params['-findany'] = true;
            $this->setRangeParams($params);
        } else {
            $params['-performscript'] = true;
        }
        return $this->getResult($this->fm->execute($params));
    }

    public function getResult($response, $result = null)
    {
        if ($this->fm->engine == "cwp") {
            return parent::getResult($response);
        } else {
            $parser      = new DataApiResult($this->fm);
            $parseResult = $parser->parse($response);
            if (FileMaker::isError($parseResult)) {
                return $parseResult;
            }
            return $parser->parsedResult;
        }
    }

    /**
     * Set Range params
     * @param array $params
     */
    protected function setRangeParams(&$params)
    {
        if ($this->skip) {
            $params['-skip'] = $this->skip;
        }
        if ($this->max) {
            $params['-max'] = $this->max;
        }
    }
}
