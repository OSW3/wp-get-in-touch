<?php

if (!class_exists('PPM_NumberType'))
{
    class PPM_NumberType extends PPM_FormType
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
                $this->getAttrStep(),
                $this->getAttrMin(),
                $this->getAttrMax(),
                $this->getAttrMaxLength(),
                $this->getAttrReadonly()
            ]);
        }
    }
}
