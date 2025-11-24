import './bootstrap';
import './image-manager';
import './visibility-manager';
import { InlineCreator, initializeInlineCreators } from './inline-creator';
import './inline-creator-init';

// Make InlineCreator available globally
window.InlineCreator = InlineCreator;
window.initializeInlineCreators = initializeInlineCreators;
