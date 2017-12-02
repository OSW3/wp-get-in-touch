<?php

if (!class_exists('PPM_CheckboxType'))
{
    class PPM_CheckboxType extends PPM_FormType
    {
        public function render()
        {
            return $this->renderField([
                $this->getAttrType(),
                $this->getAttrName(),
                $this->getAttrId(),
                $this->getAttrClass(),
                $this->getAttrValue(),
                $this->getAttrChecked(),
                $this->getAttrRequired(),
                $this->getAttrDisabled(),
                $this->getAttrReadonly()
            ]);
        }
    }
}
