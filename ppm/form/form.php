<?php

if (!class_exists('PPM_FormType'))
{
    abstract class PPM_FormType
    {
        private $errors = [];
        protected $config = null;
        private $schemaID = null;
        private $attributes = null;
        private $type = null;
        private $name = null;
        private $id = null;
        private $value = null;
        private $placeholder = null;
        private $class = null;
        private $cols = null;
        private $rows = null;
        private $checked = null;
        private $required = null;
        // private $requiredSymbol;
        private $disabled = null;
        private $readonly = null;
        private $multiple = null;
        private $choices = null;
        private $step = null;
        private $min = null;
        private $max = null;
        private $maxlength = null;
        
        private $hasLabelTag = false;
        private $hasWrapper = false;
        private $hasError = false;
        private $label = null;
        private $helper = null;

        public function __construct( $params )
        {
            $this->config       = isset($params['config']) ? $params['config'] : false;
            $this->attributes   = isset($params['attributes']) ? $params['attributes'] : false;
            $this->hasLabelTag  = isset($params['addLabelTag']) ? $params['addLabelTag'] : false;
            $this->hasWrapper   = isset($params['addWrapper']) ? $params['addWrapper'] : false;
            $this->errors       = isset($params['errors']) ? $params['errors'] : [];
            $this->schemaID     = isset($params['schemaID']) ? $params['schemaID'] : null;
            $attrNameAsArray    = isset($params['attrNameAsArray']) ? $params['attrNameAsArray'] : false;

            $this->setKey();
            $this->setType();
            $this->setMultiple();
            $this->setName( $attrNameAsArray );
            $this->setId();
            $this->setValue();
            $this->setRequired();
            $this->setPlaceholder();
            $this->setClass();
            $this->setCols();
            $this->setRows();
            $this->setChecked();
            $this->setDisabled();
            $this->setReadonly();
            $this->setTag();
            $this->setChoices();
            $this->setStep();

            $this->setMin();
            $this->setMax();
            $this->setMaxLength();

            $this->setLabel();
            $this->setHelper();
        }

        protected function renderField( $attributes, $additional=[] )
        {
            $output = null;
            $attributes = trim(implode(" ", $attributes));

            // Form control
            switch ( strtolower( $this->getTag() ) )
            {
                default:
                    $control = $this->getHtmlTag_input( $attributes );
                    break;
                    
                case 'choices':
                case 'choices_multiple':
                    $control = $this->getHtmlTag_select( $attributes );
                    break;
                    
                case 'choices_as_radio':
                case 'choices_as_checkbox':
                    $control = $this->getHtmlChoices();
                    break;
                
                case 'textarea':
                    $control = $this->getHtmlTag_textarea( $attributes );
                    break;
            }

            // The label
            if ($this->hasLabelTag) $output.= $this->getHtmlTag_label( $additional );

            $output.= $control;

            // The Error
            $output.= $this->getHtmlTag_error();

            // The Helper
            $output.= $this->getHtmlTag_helper();

            // The wrapper
            return implode(null,[
                isset($additional['before_field']) ? $additional['before_field'] : null,
                ($this->hasWrapper) ? $this->getHtmlTag_wrapper( $output ) : $output,
                isset($additional['after_field']) ? $additional['after_field'] : null
            ]);
        }


        /**
         * HTML Tags
         */
        private function getHtmlTag_input( $attributes )
        {
            return "<input $attributes />";
        }
        private function getHtmlTag_select( $attributes )
        {
            return "<select $attributes >". $this->getHtmlChoices() ."</select>";
        }
        private function getHtmlTag_textarea( $attributes )
        {
            return "<textarea $attributes >". $this->getValue() ."</textarea>";
        }
        private function getHtmlTag_wrapper( $control )
        {
            return "<div class=\"wpppm\">". $control ."</div>";
        }
        private function getHtmlTag_helper()
        {
            if (!empty($this->getHelper()))
            {
                return "<p class=\"description helper\">". $this->getHelper() ."</p>";
            }
            return false;
        }
        private function getHtmlTag_error()
        {
            // Define if we show the error message
            $show_error = true;
            if (isset($this->attributes->show_error) && is_bool($this->attributes->show_error))
            {
                $show_error = $this->attributes->show_error;
            }

            $has_error = $this->getHasError();

            if ($show_error && $has_error)
            {
                if (isset( $has_error['message'] ))
                {
                    $message = $has_error['message'];
                }
                else
                {
                    $message = __("This field is not valid.", WPPPM_TEXTDOMAIN);
                }
                return "<div class=\"notice has-error\">". $message ."</div>";
            }
            return null;
        }
        protected function getHtmlTag_label( $additional=[] )
        {
            $output = null;
            $show_label = true;

            if ((isset($additional['show_label']) && $additional['show_label'] === false) || empty($this->getLabel()) )
            {
                $show_label = false;
            }

            if ($show_label)
            {
                $output.= isset($additional['before_label']) ? $additional['before_label'] : null;
                $output.= "<label for=\"". $this->getId() ."\">". stripslashes($this->getLabel()) ."</label>";
                $output.= isset($additional['after_label']) ? $additional['after_label'] : null;
            }

            return $output;
        }
        

        /**
         * Set Tag
         */
        private function setTag()
        {
            switch ($this->getType())
            {
                case 'choices':
                    $this->tag = "choices";

                    if ( isset($this->attributes->expanded) && true === $this->attributes->expanded )
                    {
                        if ($this->getMultiple())
                        {
                            $this->tag = "choices_as_checkbox";
                        }
                        else
                        {
                            $this->tag = "choices_as_radio";
                        }
                    }
                    else if ($this->getMultiple())
                    {
                        $this->tag = "choices_multiple";
                    }
                    break;
                    
                case 'textarea':
                    $this->tag = "textarea";
                    break;
                    
                default:
                    $this->tag = "input";
                    break;
            }

            
        }
        /**
         * Get Tag Value
         */
        private function getTag()
        {
            return $this->tag;
        }


        /**
         * Get SchemaID
         */
        public function getSchemaID()
        {
            return $this->schemaID;
        }
        

        /**
         * Set Key
         */
        private function setKey()
        {
            $this->key = $this->attributes->key;
        }
        /**
         * Get Key
         */
        public function getKey()
        {
            return $this->key;
        }


        public function getHasError()
        {
            if (isset($this->errors[$this->getKey()]))
            {
                return $this->errors[$this->getKey()];
            }
            
            return false;
        }


        /**
         * Set Type
         */
        private function setType()
        {
            $value = "text";

            if (isset($this->attributes->type) )
            {
                switch ($this->attributes->type) 
                {
                    // case 'text':
                    //     break;
                    
                    default:
                        $value = $this->attributes->type ;
                        break;
                }
            }
            
            $attribute = 'type="'.$value.'"';
            
            $this->type = (object) [
                "value" => $value,
                "attribute" => $attribute,
            ];
        }
        /**
         * Get Type Value
         */
        public function getType()
        {
            return $this->type->value;
        }
        /**
         * Get Type Attribute
         */
        public function getAttrType()
        {
            return $this->type->attribute;
        }
        
        
        /**
         * Set Max
         */
        private function setMax()
        {
            $value = isset($this->attributes->max) 
                ? $this->attributes->max 
                : null;

            $attribute = null;
            
            if (null != $value)
            {
                switch ( $this->getType() )
                {
                    case 'date':
                        if ('today' === strtolower($value))
                            $attribute = 'max="'.date('Y-m-d').'"';
                        else
                            $attribute = 'max="'.$value.'"';
                        break;
                    
                    default:
                        $attribute = 'max="'.$value.'"';
                        break;
                }
            }
                        
            $this->max = (object) [
                "value" => $value,
                "attribute" => $attribute,
            ];
        }
        /**
         * Get Max Value
         */
        public function getMax()
        {
            return $this->max->value;
        }
        /**
         * Get Max Attribute
         */
        public function getAttrMax()
        {
            return $this->max->attribute;
        }
        
        
        /**
         * Set MaxLength
         */
        private function setMaxLength()
        {
            $value = isset($this->attributes->maxlength) 
                ? $this->attributes->maxlength 
                : null;
            
            $attribute = null;
            if (null !== $value)
            {
                $attribute = 'maxlength="'.$value.'"';
            }
            
            $this->maxlength = (object) [
                "value" => $value,
                "attribute" => $attribute,
            ];
        }
        /**
         * Get MaxLength Value
         */
        public function getMaxLength()
        {
            return $this->maxlength->value;
        }
        /**
         * Get MaxLength Attribute
         */
        public function getAttrMaxLength()
        {
            return $this->maxlength->attribute;
        }
        
        
        /**
         * Set Min
         */
        private function setMin()
        {
            $value = isset($this->attributes->min) 
                ? $this->attributes->min 
                : null;
                       
            $attribute = null;
            
            if (null != $value)
            {
                switch ( $this->getType() )
                {
                    case 'date':
                        if ('today' === strtolower($value))
                            $attribute = 'min="'.date('Y-m-d').'"';
                        else
                            $attribute = 'min="'.$value.'"';
                        break;
                    
                    default:
                        $attribute = 'min="'.$value.'"';
                        break;
                }
            }
            
            $this->min = (object) [
                "value" => $value,
                "attribute" => $attribute,
            ];
        }
        /**
         * Get Min Value
         */
        public function getMin()
        {
            return $this->min->value;
        }
        /**
         * Get Min Attribute
         */
        public function getAttrMin()
        {
            return $this->min->attribute;
        }
        

        /**
         * Set Name
         */
        private function setName( $attrNameAsArray = false )
        {
            $value = isset($this->attributes->key) 
                ? $this->attributes->key 
                : null;
                
            $attribute = null;

            if (null != $value)
            {
                $attribute = $attrNameAsArray === true
                    ? $this->config->Namespace.'['.$value.']'
                    : $value;
                
                if (true === $this->getMultiple() && in_array($this->getType(), ['file']))
                {
                    $attribute.= "[]";
                }

                $attribute = 'name="'.$attribute.'"';
            }
            
            $this->name = (object) [
                "value" => $value,
                "attribute" => $attribute,
            ];
        }
        /**
         * Get Name Value
         */
        public function getName()
        {
            return $this->name->value;
        }
        /**
         * Get Name Attribute
         */
        public function getAttrName()
        {
            return $this->name->attribute;
        }
        

        /**
         * Set Id
         */
        private function setId()
        {
            $value = isset($this->attributes->key) 
                ? $this->config->Prefix.$this->attributes->key 
                : null;
                
            $attribute = null != $value 
                ? 'id="'.$value.'"'
                : null;
            
            $this->id = (object) [
                "value" => $value,
                "attribute" => $attribute,
            ];
        }
        /**
         * Get Id Value
         */
        public function getId()
        {
            return $this->id->value;
        }
        /**
         * Get Id Attribute
         */
        public function getAttrId()
        {
            return $this->id->attribute;
        }
        

        /**
         * Set Value
         */
        private function setValue()
        {
            $value = get_post_meta(
                get_post()->ID, 
                $this->attributes->key, 
                true
            );

            if (empty($value))
            {
                $value = isset($this->attributes->value) 
                    ? $this->attributes->value
                    : null;
                
                if (is_string($value))
                {
                    $value = addslashes(__($value, $this->config->Namespace ));
                }
            }
            
            if (null == $value)
            {
                $value = isset($this->attributes->default) 
                    ? $this->attributes->default 
                    : null;
            }

            $attribute = null;

            if (null != $value)
            {
                switch ( $this->getType() )
                {
                    case 'checkbox':
                        $attribute = 'value="on"';
                        break;

                    case 'date':
                        if ('today' === strtolower($value))
                            $attribute = 'value="'.date('Y-m-d').'"';
                        else
                            $attribute = 'value="'.$value.'"';
                        break;
                    
                    default:
                        $attribute = 'value="'.$value.'"';
                        break;
                }
            }

            $this->value = (object) [
                "value" => $value,
                "attribute" => $attribute,
            ];
        }
        /**
         * Get Value Value
         */
        public function getValue()
        {
            return $this->value->value;
        }
        /**
         * Get Value Attribute
         */
        public function getAttrValue()
        {
            return $this->value->attribute;
        }
        

        /**
         * Set Placeholder
         */
        private function setPlaceholder()
        {
            $value = isset($this->attributes->placeholder) 
                ? addslashes(__($this->attributes->placeholder, $this->config->Namespace ))
                : null;
            
            if (null != $value)
            {
                $value.= $this->getRequiredSymbol();
            }

            $attribute = null != $value 
                ? 'placeholder="'.$value.'"'
                : null;

            $this->placeholder = (object) [
                "value" => $value,
                "attribute" => $attribute,
            ];
        }
        /**
         * Get Placeholder Value
         */
        public function getPlaceholder()
        {
            return $this->placeholder->value;
        }
        /**
         * Get Placeholder Attribute
         */
        public function getAttrPlaceholder()
        {
            return $this->placeholder->attribute;
        }
        

        /**
         * Set Class
         */
        private function setClass()
        {
            $value = isset($this->attributes->class) 
                ? $this->attributes->class 
                : null;
            
            if ($this->getHasError())
            {
                $value.= " has-error";
            }

            $attribute = null != $value 
                ? 'class="'.$value.'"'
                : null;

            $this->class = (object) [
                "value" => $value,
                "attribute" => $attribute,
            ];
        }
        /**
         * Get Class Value
         */
        public function getClass()
        {
            return $this->class->value;
        }
        /**
         * Get Class Attribute
         */
        public function getAttrClass()
        {
            return $this->class->attribute;
        }
        

        /**
         * Set Cols
         */
        private function setCols()
        {
            $value = null;

            if ( "textarea" === $this->getType() )
            {
                $value = isset($this->attributes->cols) 
                    ? intval($this->attributes->cols)
                    : null;
            }

            $attribute = null != $value 
                ? 'cols="'.$value.'"'
                : null;

            $this->cols = (object) [
                "value" => $value,
                "attribute" => $attribute,
            ];
        }
        /**
         * Get Cols Value
         */
        public function getCols()
        {
            return $this->cols->value;
        }
        /**
         * Get Cols Attribute
         */
        public function getAttrCols()
        {
            return $this->cols->attribute;
        }
        

        /**
         * Set Rows
         */
        private function setRows()
        {
            $value = null;

            if ( "textarea" === $this->getType() )
            {
                $value = isset($this->attributes->rows) 
                    ? intval($this->attributes->rows)
                    : null;
            }

            $attribute = null != $value 
                ? 'rows="'.$value.'"'
                : null;

            $this->rows = (object) [
                "value" => $value,
                "attribute" => $attribute,
            ];
        }
        /**
         * Get Rows Value
         */
        public function getRows()
        {
            return $this->rows->value;
        }
        /**
         * Get Rows Attribute
         */
        public function getAttrRows()
        {
            return $this->rows->attribute;
        }
        

        /**
         * Set Checked
         */
        private function setChecked()
        {
            $value = false;

            if ( "checkbox" === $this->getType() )
            {
                $value = "on" === $this->getValue();
            }

            $attribute = false !== $value 
                ? 'checked'
                : null;

            $this->checked = (object) [
                "value" => $value,
                "attribute" => $attribute,
            ];
        }
        /**
         * Get Checked Value
         */
        public function getChecked()
        {
            return $this->checked->value;
        }
        /**
         * Get Checked Attribute
         */
        public function getAttrChecked()
        {
            return $this->checked->attribute;
        }
        

        /**
         * Set Required
         */
        private function setRequired()
        {
            $value = isset($this->attributes->required) 
                ? $this->attributes->required
                : false;

            $attribute = true === $value 
                ? 'required="required"'
                : null;

            $this->required = (object) [
                "value" => $value,
                "attribute" => $attribute,
            ];
        }
        /**
         * Get Required Value
         */
        public function getRequired()
        {
            return $this->required->value;
        }
        /**
         * Get Required Attribute
         */
        public function getAttrRequired()
        {
            return $this->required->attribute;
        }

        private function getRequiredSymbol()
        {
            $symbol = "*";
            
            if ($this->getRequired())
            {
                if (isset($this->attributes->required_symbol))
                {
                    $symbol = $this->attributes->required_symbol;

                    if (false === $symbol)
                    {
                        $symbol = null;
                    }
                }
                return " ".$symbol;
            }
        }
        

        /**
         * Set Disabled
         */
        private function setDisabled()
        {
            $value = isset($this->attributes->disabled) 
                ? $this->attributes->disabled
                : false;

            $attribute = true === $value 
                ? 'disabled="disabled"'
                : null;

            $this->disabled = (object) [
                "value" => $value,
                "attribute" => $attribute,
            ];
        }
        /**
         * Get Disabled Value
         */
        public function getDisabled()
        {
            return $this->disabled->value;
        }
        /**
         * Get Disabled Attribute
         */
        public function getAttrDisabled()
        {
            return $this->disabled->attribute;
        }
        

        /**
         * Set Readonly
         */
        private function setReadonly()
        {
            $value = isset($this->attributes->readonly) 
                ? $this->attributes->readonly
                : false;

            $attribute = true === $value 
                ? 'readonly="readonly"'
                : null;

            $this->readonly = (object) [
                "value" => $value,
                "attribute" => $attribute,
            ];
        }
        /**
         * Get Readonly Value
         */
        public function getReadonly()
        {
            return $this->readonly->value;
        }
        /**
         * Get Readonly Attribute
         */
        public function getAttrReadonly()
        {
            return $this->readonly->attribute;
        }
        

        /**
         * Set Multiple
         */
        private function setMultiple()
        {
            $value = false;

            if ( in_array($this->getType(),["choices", "file"]) )
            {
                $value = isset($this->attributes->multiple) 
                    ? $this->attributes->multiple
                    : false;
            }

            $attribute = true === $value 
                ? 'multiple="multiple"'
                : null;

            $this->multiple = (object) [
                "value" => $value,
                "attribute" => $attribute,
            ];
        }
        /**
         * Get Multiple Value
         */
        public function getMultiple()
        {
            return $this->multiple->value;
        }
        /**
         * Get Multiple Attribute
         */
        public function getAttrMultiple()
        {
            return $this->multiple->attribute;
        }
        

        /**
         * Set Choices
         */
        private function setChoices()
        {
            $value = null;
            $html = null;

            if ( "choices" === $this->getType() )
            {
                if (isset($this->attributes->choices))
                {
                    if (is_string($this->attributes->choices))
                    {
                        $value = PPM::tryToDo( $this->attributes->choices );
                    }
                    else
                    {
                        $value = $this->attributes->choices;
                    }
                }
            }

            if (is_array($value))
            {
                $selected_value = $this->getValue();

                foreach ($value as $key => $choice)
                {
                    $is_selected = $key == $selected_value;

                    $value[$key] = [
                        "label" => $choice,
                        "value" => $key,
                        "is_selected" => $is_selected,
                    ];

                    switch ($this->getTag())
                    {
                        case 'choices_multiple':
                                $this->name = (object) [
                                    "value" => $this->getName(),
                                    "attribute" => 'name="'.$this->config->Namespace.'['.$this->getName().'][]"'
                                ];
                                
                                $selected = null;
                                if (is_array($this->getValue()))
                                {
                                    $selected = in_array($value[$key]['value'], $this->getValue()) ? " selected" : null;
                                }
                                
                                $html.= "<option value=\"".$value[$key]['value']."\"".$selected.">";
                                $html.= $value[$key]['label'];
                                $html.= "</option>";
                            break;

                        case 'choices_as_radio':
                            $html.= !empty($html) ? "<br>\n" : null;

                            $this->type = (object) [
                                "value" => "radio",
                                "attribute" => 'type="radio"'
                            ];
                            
                            $this->value = (object) [
                                "value" => $value[$key]['value'],
                                "attribute" => 'value="'.$value[$key]['value'].'"'
                            ];
                            
                            $this->checked = (object) [
                                "value" => $value[$key]['is_selected'] ? true : false,
                                "attribute" => $value[$key]['is_selected'] ? "checked" : null
                            ];
                            
                            $html.= "<label>";
                            $html.= $this->getHtmlTag_input(implode(" ",[
                                $this->getAttrType(),
                                $this->getAttrName(),
                                $this->getAttrId(),
                                $this->getAttrClass(),
                                $this->getAttrValue(),
                                $this->getAttrRequired(),
                                $this->getAttrDisabled(),
                                $this->getAttrReadonly(),
                                $this->getAttrChecked(),
                            ]));
                            $html.= $value[$key]['label'];
                            $html.= "</label>\n";
                            break;

                        case 'choices_as_checkbox':
                            $html.= !empty($html) ? "<br>\n" : null;

                            $this->type = (object) [
                                "value" => "checkbox",
                                "attribute" => 'type="checkbox"'
                            ];
                            
                            $this->name = (object) [
                                "value" => $this->getName(),
                                "attribute" => 'name="'.$this->config->Namespace.'['.$this->getName().']['.$value[$key]['value'].']"'
                            ];

                            $this->checked = (object) [
                                "value" => false,
                                "attribute" =>  null
                            ];
                            if (is_array($this->getValue()))
                            {
                                $this->checked = (object) [
                                    "value" => in_array($value[$key]['value'], $this->getValue()) ? true : false,
                                    "attribute" => in_array($value[$key]['value'], $this->getValue()) ? "checked" : null
                                ];
                            }

                            
                            $html.= "<label>";
                            $html.= $this->getHtmlTag_input(implode(" ",[
                                $this->getAttrType(),
                                $this->getAttrName(),
                                $this->getAttrId(),
                                $this->getAttrClass(),
                                'value="'.$value[$key]['value'].'"',
                                $this->getAttrRequired(),
                                $this->getAttrDisabled(),
                                $this->getAttrReadonly(),
                                $this->getAttrChecked(),
                            ]));
                            $html.= $value[$key]['label'];
                            $html.= "</label>\n";
                            break;

                        default:
                            $selected = $value[$key]['is_selected'] ? " selected" : null;
                            $html.= "<option value=\"".$value[$key]['value']."\"".$selected.">";
                            $html.= $value[$key]['label'];
                            $html.= "</option>";
                            break;
                    }
                }
            }

            $this->choices = (object) [
                "value" => $value,
                "html" => $html,
            ];
        }
        /**
         * Get Choices Value
         */
        private function getChoices()
        {
            return $this->choices->value;
        }
        /**
         * Get Choices HTML
         */
        private function getHtmlChoices()
        {
            return $this->choices->html;
        }
        

        /**
         * Set Step
         */
        private function setStep()
        {
            $value = isset($this->attributes->step) 
                ? $this->attributes->step 
                : null;
                
            $attribute = null != $value 
                ? 'step="'.$value.'"'
                : null;
            
            $this->step = (object) [
                "value" => $value,
                "attribute" => $attribute,
            ];
        }
        /**
         * Get Step Value
         */
        public function getStep()
        {
            return $this->step->value;
        }
        /**
         * Get Step Attribute
         */
        public function getAttrStep()
        {
            return $this->step->attribute;
        }
        

        /**
         * Set Label
         */
        private function setLabel ()
        {
            $label = isset($this->attributes->label) 
                ? addslashes(__($this->attributes->label, $this->config->Namespace ))
                : null;


            if (null != $label)
            {
                $label.= " <span class=\"required-symbol\">";
                $label.= trim($this->getRequiredSymbol());
                $label.= "<span>";
            }

            $this->label = $label;
        }
        /**
         * Get Label
         */
        private function getLabel ()
        {
            return $this->label;
        }
        

        /**
         * Set Helper
         */
        private function setHelper ()
        {
            $this->helper = isset($this->attributes->helper) 
                ? addslashes(__($this->attributes->helper, $this->config->Namespace ))
                : null;
        }
        /**
         * Get Helper
         */
        private function getHelper ()
        {
            return $this->helper;
        }
    }
}
