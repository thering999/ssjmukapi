<?php

namespace App\Support;

class View
{
    /**
     * Render a view file
     * 
     * @param string $view The view path relative to src/Views (e.g., 'home', 'layouts/main')
     * @param array $data Data to pass to the view
     */
    public static function render(string $view, array $data = []): void
    {
        // Extract data to variables
        extract($data);

        $viewPath = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            throw new \Exception("View file not found: {$viewPath}");
        }

        require $viewPath;
    }
}
