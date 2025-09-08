<?php

namespace Ksfraser\UIRenderer\Components;

use Ksfraser\UIRenderer\Contracts\ComponentInterface;
use Ksfraser\UIRenderer\DTOs\CardDto;

/**
 * Card Component - Renders content cards
 * Follows Single Responsibility Principle
 */
class CardComponent implements ComponentInterface {
    /** @var CardDto */
    private $cardDto;
    
    public function __construct(CardDto $cardDto) {
        $this->cardDto = $cardDto;
    }
    
    public function toHtml() {
        $iconHtml = $this->cardDto->icon ? "<span class='card-icon'>{$this->cardDto->icon}</span> " : '';
        $actionsHtml = $this->renderActions();
        
        return "
        <div class='card {$this->cardDto->type}'>
            <h3>{$iconHtml}{$this->cardDto->title}</h3>
            <div class='card-content'>
                {$this->cardDto->content}
            </div>
            {$actionsHtml}
        </div>";
    }
    
    private function renderActions() {
        if (empty($this->cardDto->actions)) {
            return '';
        }
        
        $actions = [];
        foreach ($this->cardDto->actions as $action) {
            $class = isset($action['class']) ? " {$action['class']}" : '';
            $actions[] = "<a href='{$action['url']}' class='btn{$class}'>{$action['label']}</a>";
        }
        
        return "<div class='card-actions'>" . implode('', $actions) . "</div>";
    }
}
