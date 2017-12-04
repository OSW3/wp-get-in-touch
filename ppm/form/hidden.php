<?php

if (!class_exists('PPM_HiddenType'))
{
    class PPM_HiddenType extends PPM_FormType
    {
        public function render()
        {
            return $this->renderField([
                $this->getAttrType(),
                $this->getAttrName(),
                $this->getAttrId(),
                $this->getAttrClass(),
                $this->getAttrValue(),
                $this->getAttrPlaceholder(),
                $this->getAttrRequired(),
                $this->getAttrDisabled(),
                $this->getAttrMaxLength(),
                $this->getAttrReadonly()
            ]);
        }
    }
}
