<?php
/**
 * Router: so khớp URL + HTTP method với danh sách route, rồi gọi Controller tương ứng.
 */
class Router
{
    private array $routes = [];

    public function get(string $path, string $handler): void  { $this->add('GET', $path, $handler); }
    public function post(string $path, string $handler): void { $this->add('POST', $path, $handler); }

    private function add(string $method, string $path, string $handler): void
    {
        // Đổi {slug} thành regex (?P<slug>[^/]+) để bắt tham số động
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path);
        $this->routes[] = [$method, '#^' . $regex . '$#', $handler];
    }

    /** Tìm route phù hợp và thực thi; không có thì trả 404. */
    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        // Bỏ phần BASE_URL (và /public nếu có) ở đầu để còn lại đường dẫn "logic"
        $path = preg_replace('#^' . preg_quote(BASE_URL, '#') . '(/public)?#', '', $path);
        $path = '/' . trim($path, '/');

        foreach ($this->routes as [$routeMethod, $regex, $handler]) {
            if ($routeMethod === $method && preg_match($regex, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                [$controllerName, $action] = explode('@', $handler);
                (new $controllerName())->$action(...array_values($params));
                return;
            }
        }
        http_response_code(404);
        echo '404 - Không tìm thấy trang';
    }
}
