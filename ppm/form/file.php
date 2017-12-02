<?php

if (!class_exists('PPM_FileType'))
{
    class PPM_FileType extends PPM_FormType
    {
        public function render( $attachments, $has_preview=false )
        {
            $prepend = null;
            $append = null;

            $previews = [];
            $show_label = !$has_preview;

            // Check if file exists
            if (is_array($attachments))
            {
                foreach ($attachments as $key => $attachment)
                {
                    $attachment['exists'] = false;

                    if (file_exists($attachment['file']))
                    {
                        $attachment['exists'] = true;
                    }

                    array_push($previews, $attachment);
                }
            }

            if (false !== $has_preview)
            {
                if ("CustomPosts" === $this->getSchemaID())
                {
                    $prepend.= $this->getHtmlTag_label();
                }

                $prepend.= "<table>";
                $prepend.= "<tr>";
                $prepend.= "<td>";
                
                $prepend.= "<div class=\"wpppm-images-preview\">";

                if (count($previews) >= 1)
                {
                    foreach ($previews as $preview)
                    {
                        $prepend.= "<div>";
                        if ($preview['exists'])
                        {
                            $src_data = wp_get_attachment_image_src($preview['attachment'], [100, 100], true);
                            $src_url = $src_data[0];
                            $src_file = pathinfo($src_url);
                            $prepend.= '<img src="'.$src_url.'" title="'.$src_file['basename'].'" width="100">';
                        }
                        else
                        {
                            $prepend.= '<img src="'.$this->config->Url.'assets/images/default.svg'.'">';
                        }
                        $prepend.= "</div>";
                    }
                }
                else
                {
                    $prepend.= "<div>";
                    $prepend.= '<img src="'.$this->config->Url.'assets/images/default.svg'.'">';
                    $prepend.= "</div>";
                }

                $prepend.= "</div>";

                $prepend.= "</td>";

                $prepend.= "<td>";
                // <input type="file">
                $append.= "</td>";
                $append.= "</tr>";
                $append.= "</table>";
            }

            return $this->renderField([
                    $this->getAttrType(),
                    $this->getAttrName(),
                    $this->getAttrId(),
                    $this->getAttrClass(),
                    $this->getAttrRequired(),
                    $this->getAttrDisabled(),
                    $this->getAttrMultiple(),
                    $this->getAttrReadonly()
                ],[
                    // "before_label" => "BEFORE_LABEL",
                    // "after_label" => "AFTER_LABEL"
                    "show_label" => $show_label,
                    "before_field" => $prepend,
                    "after_field" => $append
                ]);
        }
    }
}
