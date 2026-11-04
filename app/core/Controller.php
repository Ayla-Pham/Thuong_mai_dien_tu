<?php
/** Controller cơ sở: cung cấp hàm render() để hiển thị View bên trong layout chung. */
abstract class Controller
{
    /**
     * @param string $view   tên view, vd 'home/index' => app/views/home/index.php
     * @param array  $data   biến truyền sang view
     * @param string $layout 'main' (khách hàng) hoặc 'admin' (trang quản trị)
     */
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        global $config;
        extract($data);
        ob_start();
        require APP_PATH . "/views/$view.php";
        $content = ob_get_clean();   // nội dung view, sẽ chèn vào layout
        require APP_PATH . "/views/layouts/$layout.php";
        unset($_SESSION['old']);     // dữ liệu form cũ chỉ dùng cho đúng 1 lần hiển thị
    }
}
