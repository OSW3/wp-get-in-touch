<?php

if (!class_exists('PPM_ChoicesType'))
{
    class PPM_ChoicesType extends PPM_FormType
    {
        public function render()
        {
            return $this->renderField([
                $this->getAttrName(),
                $this->getAttrId(),
                $this->getAttrClass(),
                $this->getAttrPlaceholder(),
                $this->getAttrMultiple(),
                $this->getAttrRequired(),
                $this->getAttrDisabled(),
                $this->getAttrReadonly()
            ]);
        }
    }
}
