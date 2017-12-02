<?php

if (!class_exists('PPM_TextareaType'))
{
    class PPM_TextareaType extends PPM_FormType
    {
        public function render()
        {
            return $this->renderField([
                $this->getAttrName(),
                $this->getAttrId(),
                $this->getAttrClass(),
                $this->getAttrPlaceholder(),
                $this->getAttrCols(),
                $this->getAttrRows(),
                $this->getAttrRequired(),
                $this->getAttrDisabled(),
                $this->getAttrReadonly()
            ]);
        }
    }
}
