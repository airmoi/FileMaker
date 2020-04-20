<?php

/**
 * @copyright Copyright (c) 2016 by 1-more-thing (http://1-more-thing.com) All rights reserved.
 * @license BSD
 */

namespace airmoi\FileMaker\Command;

use airmoi\FileMaker\FileMaker;
use airmoi\FileMaker\FileMakerException;
use airmoi\FileMaker\Object\Field;

trait CommandTrait
{

    /**
     * Implementation. This is the object that actually implements the
     * command base.
     *
     * @var FileMaker
     * @access public
     */
    public $fm;

    /**
     *
     * @var string
     */
    protected $layout;

    /**
     * @return \airmoi\FileMaker\FileMakerException|\airmoi\FileMaker\Object\Layout
     * @throws FileMakerException
     */
    public function getLayout()
    {
        return $this->fm->getLayout($this->layout);
    }

    /**
     * Get the field "type" (date/text/number...)
     * @param $fieldName
     *
     * @return null|Field|FileMakerException Field object, if successful.
     */
    public function getFieldResult($fieldName)
    {
        try {
            $layout = $this->fm->getLayout($this->layout);
            if (FileMaker::isError($layout)) {
                /** @psalm-suppress InvalidThrow */
                throw $layout;
            }
            $field = $layout->getField($fieldName);
            if(FileMaker::isError($field)){
                /** @psalm-suppress InvalidThrow */
                throw $field;
            }
        } catch (FileMakerException $e) {
            return null;
        }
        return $field->result;
    }
}
