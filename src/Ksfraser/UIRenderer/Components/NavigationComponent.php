<?php

namespace Ksfraser\UIRenderer\Components;

use Ksfraser\UIRenderer\Contracts\ComponentInterface;
use Ksfraser\UIRenderer\DTOs\NavigationDto;

/**
 * Navigation Component - Renders navigation header
 * Follows Single Responsibility Principle
 */
class NavigationComponent implements ComponentInterface {
    /** @var NavigationDto */
    private $navigationDto;
    
    public function __construct(NavigationDto $navigationDto) {
        $this->navigationDto = $navigationDto;
    }
    
    public function toHtml() {
        $adminClass = $this->navigationDto->isAdmin ? ' admin' : '';
        $userDisplay = $this->navigationDto->user['username'] ?? 'Guest';
        $userStatus = $this->navigationDto->isAuthenticated ? 'Authenticated' : 'Guest';
        
        $menuLinks = $this->renderMenuItems();
        
        return "
        <div class='nav-header{$adminClass}'>
            <div class='nav-container'>
                <h1 class='nav-title'>{$this->navigationDto->title}</h1>
                <div class='nav-user'>
                    <div class='nav-links'>
                        {$menuLinks}
                    </div>
                    <span>👤 {$userDisplay} ({$userStatus})</span>
                </div>
            </div>
        </div>";
    }
    
    private function renderMenuItems(): string {
        if (empty($this->navigationDto->menuItems)) {
            return '';
        }
        
        $links = [];
        foreach ($this->navigationDto->menuItems as $item) {
            $activeClass = ($item['active'] ?? false) ? ' active' : '';
            $links[] = "<a href='{$item['url']}' class='{$activeClass}'>{$item['label']}</a>";
        }
        
        return implode('', $links);
    }
}
