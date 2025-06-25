<?php

declare(strict_types=1);

namespace LePostClient\Post;

use LePostClient\Exceptions\ContentGenerationException;

/**
 * Handles the assembly of post content with images.
 *
 * @since 1.0.0
 */
class PostAssembler {
    /**
     * Assembles the final post content by inserting images into the HTML structure.
     *
     * The HTML content is expected to be structured with <section> tags.
     * Images are inserted after the first content section, and then before each closing </section> tag,
     * cycling through the provided image URLs if there are more sections than images, or until
     * all images are used if there are more images than sections.
     *
     * @since 1.0.0
     * 
     * @param string $html_content The HTML content string with <section> tags.
     * @param array  $image_urls   An array of image URLs to insert.
     * @return string The HTML content with images inserted.
     * @throws ContentGenerationException When content cannot be parsed properly.
     */
    public function assemble_content(string $html_content, array $image_urls): string {
        try {
            if (empty($image_urls) || empty($html_content)) {
                return $html_content;
            }

            // Normalize line endings to prevent issues with regex across different OS newlines
            $html_content = str_replace(["\r\n", "\r"], "\n", $html_content);

            $output_html = '';
            $last_pos = 0;
            $section_close_tag = '</section>';
            $tag_len = strlen($section_close_tag);
            $image_count = count($image_urls);
            $current_image_index = 0;
            $section_count = 0;

            while (($pos = stripos($html_content, $section_close_tag, $last_pos)) !== false && $current_image_index < $image_count) {
                $section_count++;
                
                // Add content up to the section closing tag
                $output_html .= substr($html_content, $last_pos, $pos - $last_pos);
                
                // Only add images after the first content section (skip intro section)
                if ($section_count > 1) {
                    // Add image before the closing tag
                    $image_url = esc_url($image_urls[$current_image_index]);
                    $image_tag = sprintf(
                        '<figure class="wp-block-image size-large"><img src="%s" alt="%s"></figure>', 
                        $image_url, 
                        esc_attr__('Generated image', 'lepostclient')
                    );
                    
                    $output_html .= $image_tag;
                    $current_image_index++;
                }
                
                // Add the section closing tag
                $output_html .= $section_close_tag;
                
                // Move position past this section
                $last_pos = $pos + $tag_len;
            }

            // Add any remaining content
            $output_html .= substr($html_content, $last_pos);

            // If no </section> tags were found, return original content
            if ($output_html === '') {
                return $html_content;
            }

            return $output_html;
        } catch (ContentGenerationException $e) {
            error_log('LePostClient Content Generation Error: ' . $e->getMessage());
            return $html_content;
        } catch (\Throwable $e) {
            error_log('LePostClient PostAssembler Error: ' . $e->getMessage());
            return $html_content;
        }
    }
} 