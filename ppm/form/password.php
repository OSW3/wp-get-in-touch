<?php

if (!class_exists('PPM_PasswordType'))
{
    class PPM_PasswordType extends PPM_FormType
    {
        public function render()
        {
            return $this->renderField([
                $this->getAttrType(),
                $this->getAttrName(),
                $this->getAttrId(),
                $this->getAttrClass(),
                $this->getAttrPlaceholder(),
                $this->getAttrRequired(),
                $this->getAttrDisabled(),
                $this->getAttrReadonly()
            ]);
        }
    }
}
