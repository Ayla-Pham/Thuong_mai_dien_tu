<?php
/** Xử lý danh sách sản phẩm (lọc/tìm kiếm) và trang chi tiết sản phẩm. */
class ShopController extends Controller
{
    /** GET /san-pham?q=&category=&author=&publisher=&min_price=&max_price=&sort=&page= */
    public function index(): void
    {
        $filters = [];
        foreach (['q', 'category', 'author', 'publisher', 'min_price', 'max_price', 'sort'] as $k) {
            $filters[$k] = trim((string)($_GET[$k] ?? ''));
        }
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 9;

        [$products, $total] = (new Product())->search($filters, $page, $perPage);
        $cat = new Category();

        $this->render('shop/index', [
            'title'       => $filters['q'] !== '' ? 'Tìm kiếm: ' . $filters['q'] : 'Tất cả sản phẩm',
            'description' => 'Tìm sách và văn phòng phẩm theo tác giả, thể loại, nhà xuất bản, khoảng giá.',
            'products'    => $products,
            'total'       => $total,
            'page'        => $page,
            'pages'       => max(1, (int)ceil($total / $perPage)),
            'filters'     => $filters,
            'categories'  => $cat->tree(),
            'authors'     => $cat->authors(),
            'publishers'  => $cat->publishers(),
        ]);
    }

    /** GET /san-pham/{slug} - chi tiết + "thường được mua cùng" + "sản phẩm liên quan". */
    public function show(string $slug): void
    {
        $model = new Product();
        $product = $model->findBySlug($slug);
        if (!$product) { http_response_code(404); echo '404 - Không tìm thấy sản phẩm'; return; }

        $this->render('shop/show', [
            'title'          => $product['name'],
            'description'    => mb_substr(strip_tags($product['description'] ?? ''), 0, 155),
            'p'              => $product,
            'info'           => $model->details((int)$product['id'], $product['publisher_id'] ? (int)$product['publisher_id'] : null, (int)$product['category_id']),
            'related'        => $model->related((int)$product['id'], (int)$product['category_id']),
            'boughtTogether' => $model->boughtTogether((int)$product['id']),
        ]);
    }
}
