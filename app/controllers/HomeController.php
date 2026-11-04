<?php
/** Xử lý trang chủ. */
class HomeController extends Controller
{
    public function index(): void
    {
        $product = new Product();
        $this->render('home/index', [
            'title'       => 'Trang chủ',
            'description' => 'BookStore - website bán sách và văn phòng phẩm uy tín, giá tốt, giao hàng nhanh.',
            'bestSellers' => $product->bestSellers(8),
            'newest'      => $product->newest(4),
            'categories'  => (new Category())->tree(),
        ]);
    }
}
