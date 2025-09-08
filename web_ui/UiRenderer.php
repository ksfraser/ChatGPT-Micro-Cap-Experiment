<?php
/**
 * UI Renderer Interface - Defines contract for all UI rendering
 * Follows Interface Segregation Principle
 */
interface UiRendererInterface {
    public function render();
}

/**
 * Component Interface - For reusable UI components
 */
interface ComponentInterface {
    public function toHtml();
}

/**
 * Navigation Data Transfer Object
 * Follows Single Responsibility Principle
 */
class NavigationDto {
    public $title;
    public $currentPage;
    public $user;
    public $isAdmin;
    public $menuItems;
    public $isAuthenticated;
    
    public function __construct(
        $title = 'Enhanced Trading System',
        $currentPage = '',
        $user = null,
        $isAdmin = false,
        $menuItems = [],
        $isAuthenticated = false
    ) {
        $this->title = $title;
        $this->currentPage = $currentPage;
        $this->user = $user;
        $this->isAdmin = $isAdmin;
        $this->menuItems = $menuItems;
        $this->isAuthenticated = $isAuthenticated;
    }
}

/**
 * Card Data Transfer Object
 */
class CardDto {
    public $title;
    public $content;
    public $type;
    public $icon;
    public $actions;
    
    public function __construct(
        $title,
        $content,
        $type = 'default',
        $icon = '',
        $actions = []
    ) {
        $this->title = $title;
        $this->content = $content;
        $this->type = $type;
        $this->icon = $icon;
        $this->actions = $actions;
    }
}

/**
 * CSS Provider - Single Responsibility for styling
 */
class CssProvider {
    public static function getBaseStyles(): string {
        return '
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0; padding: 0; background: #f5f5f5; 
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card { 
            background: white; padding: 20px; margin: 10px 0; border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .success { border-left: 4px solid #28a745; }
        .info { border-left: 4px solid #007bff; }
        .warning { border-left: 4px solid #ffc107; }
        .error { border-left: 4px solid #dc3545; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .btn { 
            display: inline-block; padding: 10px 20px; background: #007bff; color: white; 
            text-decoration: none; border-radius: 4px; margin: 5px; transition: background-color 0.3s;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        ';
    }
    
    public static function getNavigationStyles(): string {
        return '
        .nav-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .nav-header.admin {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-title {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .nav-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .nav-links {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        .nav-links a.active {
            background: rgba(255,255,255,0.3);
            font-weight: bold;
        }
        .admin-badge {
            background: #ffffff;
            color: #dc3545;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            border: 1px solid #ffffff;
        }
        .nav-header.admin .admin-badge {
            background: #ffffff;
            color: #dc3545;
            border: 2px solid #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .user-info {
            font-size: 14px;
        }
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
        ';
    }
}

/**
 * Navigation Component - Single Responsibility for navigation rendering
 */
class NavigationComponent implements ComponentInterface {
    private $navData;
    
    public function __construct($navData) {
        $this->navData = $navData;
    }
    
    public function toHtml() {
        $adminClass = $this->navData->isAdmin ? ' admin' : '';
        $html = '<div class="nav-header' . $adminClass . '">';
        $html .= '<div class="nav-container">';
        $html .= '<h1 class="nav-title">' . htmlspecialchars($this->navData->title) . '</h1>';
        
        $html .= '<div class="nav-user">';
        
        if ($this->navData->isAuthenticated) {
            $html .= $this->renderNavigationLinks();
            $html .= $this->renderUserInfo();
        } else {
            $html .= $this->renderPublicLinks();
        }
        
        $html .= '</div>'; // nav-user
        $html .= '</div>'; // nav-container
        $html .= '</div>'; // nav-header
        
        return $html;
    }
    
    private function renderNavigationLinks() {
        $html = '<div class="nav-links">';
        
        foreach ($this->navData->menuItems as $item) {
            $activeClass = $item['active'] ? ' active' : '';
            $adminTitle = isset($item['admin_only']) ? ' title="Admin Only"' : '';
            
            $html .= '<a href="' . htmlspecialchars($item['url']) . '" class="nav-link' . $activeClass . '"' . $adminTitle . '>';
            $html .= htmlspecialchars($item['label']);
            $html .= '</a>';
        }
        
        $html .= '<a href="logout.php" class="nav-link">🚪 Logout</a>';
        $html .= '</div>';
        
        return $html;
    }
    
    private function renderUserInfo() {
        $html = '<div class="user-info">';
        $html .= '<span>👤 ' . htmlspecialchars($this->navData->user['username'] ?? 'Unknown') . '</span>';
        
        if ($this->navData->isAdmin) {
            $html .= ' <span class="admin-badge">ADMIN</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    private function renderPublicLinks() {
        return '<div class="nav-links">
                    <a href="login.php" class="nav-link">🔐 Login</a>
                    <a href="register.php" class="nav-link">📝 Register</a>
                </div>';
    }
}

/**
 * Card Component - Single Responsibility for card rendering
 */
class CardComponent implements ComponentInterface {
    private $cardData;
    
    public function __construct($cardData) {
        $this->cardData = $cardData;
    }
    
    public function toHtml() {
        $typeClass = $this->cardData->type !== 'default' ? ' ' . $this->cardData->type : '';
        
        $html = '<div class="card' . $typeClass . '">';
        $html .= '<h3>' . $this->cardData->icon . ' ' . htmlspecialchars($this->cardData->title) . '</h3>';
        $html .= '<p>' . $this->cardData->content . '</p>';
        
        if (!empty($this->cardData->actions)) {
            foreach ($this->cardData->actions as $action) {
                $html .= '<a href="' . htmlspecialchars($action['url']) . '" class="' . htmlspecialchars($action['class']) . '">';
                $html .= $action['icon'] . ' ' . htmlspecialchars($action['label']);
                $html .= '</a>';
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
}

/**
 * Page Renderer - Main UI rendering orchestrator
 * Follows Single Responsibility and Dependency Injection principles
 */
class PageRenderer implements UiRendererInterface {
    private $title;
    private $navigation;
    private $components;
    private $customCss;
    
    public function __construct(
        $title,
        $navigation,
        $components = [],
        $customCss = ''
    ) {
        $this->title = $title;
        $this->navigation = $navigation;
        $this->components = $components;
        $this->customCss = $customCss;
    }
    
    public function addComponent($component) {
        $this->components[] = $component;
    }
    
    public function render() {
        $html = $this->renderDocumentStart();
        $html .= $this->navigation->toHtml();
        $html .= '<div class="container">';
        
        foreach ($this->components as $component) {
            $html .= $component->toHtml();
        }
        
        $html .= '</div>';
        $html .= $this->renderDocumentEnd();
        
        return $html;
    }
    
    private function renderDocumentStart() {
        return '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars($this->title) . '</title>
            <style>
                ' . CssProvider::getBaseStyles() . '
                ' . CssProvider::getNavigationStyles() . '
                ' . $this->customCss . '
            </style>
        </head>
        <body>';
    }
    
    private function renderDocumentEnd() {
        return '</body></html>';
    }
}

/**
 * UI Factory - Creates UI components with proper dependencies
 * Follows Dependency Injection and Factory patterns
 */
class UiFactory {
    public static function createNavigationComponent(
        $title,
        $currentPage,
        $user,
        $isAdmin,
        $menuItems,
        $isAuthenticated
    ) {
        $navDto = new NavigationDto(
            $title,
            $currentPage,
            $user,
            $isAdmin,
            $menuItems,
            $isAuthenticated
        );
        
        return new NavigationComponent($navDto);
    }
    
    public static function createCard(
        $title,
        $content,
        $type = 'default',
        $icon = '',
        $actions = []
    ) {
        $cardDto = new CardDto($title, $content, $type, $icon, $actions);
        return new CardComponent($cardDto);
    }
    
    public static function createPageRenderer(
        $title,
        $navigation,
        $components = [],
        $customCss = ''
    ) {
        return new PageRenderer($title, $navigation, $components, $customCss);
    }
}
?>
