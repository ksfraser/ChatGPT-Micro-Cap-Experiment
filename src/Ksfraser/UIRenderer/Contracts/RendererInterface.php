<?php

namespace Ksfraser\UIRenderer\Contracts;

/**
 * UI Renderer Interface - Defines contract for all UI rendering
 * Follows Interface Segregation Principle
 */
interface RendererInterface {
    public function render();
}

/**
 * Component Interface - For reusable UI components
 */
interface ComponentInterface {
    public function toHtml();
}
