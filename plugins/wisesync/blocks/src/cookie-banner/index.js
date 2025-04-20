/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './index.css';

/**
 * Register the block
 */
registerBlockType('my-plugin/footer-only-block', {
  edit: ({ context }) => {
    const blockProps = useBlockProps();
    
    // Check if we're in a footer template part
    const isFooter = context?.templateSlug === 'footer';
    
    // Check if this block already exists in the current template
    const blockCount = useSelect((select) => {
      const blocks = select('core/block-editor').getBlocks();
      return blocks.filter(block => block.name === 'my-plugin/footer-only-block').length;
    }, []);
    
    // If we're not in a footer template or the block is already used, show a warning
    if (!isFooter) {
      return (
        <div {...blockProps} className="footer-only-block-warning">
          <p>{__('This block can only be used in footer template parts.', 'my-plugin')}</p>
        </div>
      );
    }
    
    // Normal block content
    return (
      <div {...blockProps}>
        <div className="footer-only-block">
          <InnerBlocks />
        </div>
      </div>
    );
  },
  
  save: () => {
    const blockProps = useBlockProps.save();
    
    return (
      <div {...blockProps}>
        <div className="footer-only-block">
          <InnerBlocks.Content />
        </div>
      </div>
    );
  },
});