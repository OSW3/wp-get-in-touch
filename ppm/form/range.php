<?php

if (!class_exists('PPM_RangeType'))
{
    class PPM_RangeType extends PPM_FormType
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
                $this->getAttrMin(),
                $this->getAttrMax(),
                $this->getAttrStep(),
                $this->getAttrReadonly()
            ]);
        }
    }
}
