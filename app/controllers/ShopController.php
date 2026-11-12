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

        // Trạng thái đánh giá của người xem: chưa đăng nhập / chưa mua / được phép đánh giá (kèm đánh giá cũ nếu có)
        $user = auth_user();
        $reviews = new Review();
        $canReview = $user && $reviews->hasPurchased($user['id'], (int)$product['id']);

        $this->render('shop/show', [
            'canReview'      => $canReview,
            'myReview'       => $canReview ? $reviews->findMine($user['id'], (int)$product['id']) : null,
            'title'          => $product['name'],
            'description'    => mb_substr(strip_tags($product['description'] ?? ''), 0, 155),
            'p'              => $product,
            'info'           => $model->details((int)$product['id'], $product['publisher_id'] ? (int)$product['publisher_id'] : null, (int)$product['category_id']),
            'related'        => $model->related((int)$product['id'], (int)$product['category_id']),
            'boughtTogether' => $model->boughtTogether((int)$product['id']),
        ]);
    }

    /** POST /san-pham/{slug}/danh-gia - gửi hoặc sửa đánh giá (chỉ khách đã mua và nhận hàng). */
    public function review(string $slug): void
    {
        csrf_verify();
        $user = require_login();
        $product = (new Product())->findBySlug($slug);
        if (!$product) { http_response_code(404); echo '404 - Không tìm thấy sản phẩm'; return; }
        $back = '/san-pham/' . $product['slug'];

        $reviews = new Review();
        // Kiểm tra quyền ở SERVER (không tin việc form có hiện hay không)
        if (!$reviews->hasPurchased($user['id'], (int)$product['id'])) {
            flash('warning', 'Chỉ khách đã mua và nhận sản phẩm này mới có thể đánh giá.');
            redirect($back);
        }
        $rating  = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if ($rating < 1 || $rating > 5)  { flash('danger', 'Vui lòng chọn số sao từ 1 đến 5.'); redirect($back . '#danh-gia'); }
        if (mb_strlen($comment) > 500)   { flash('danger', 'Nhận xét tối đa 500 ký tự.'); redirect($back . '#danh-gia'); }

        $reviews->save($user['id'], (int)$product['id'], $rating, $comment !== '' ? $comment : null);
        flash('success', 'Cảm ơn bạn đã đánh giá sản phẩm!');
        redirect($back . '#danh-gia');
    }

    /** POST /san-pham/{slug}/danh-gia/xoa - xóa đánh giá của chính mình. */
    public function deleteReview(string $slug): void
    {
        csrf_verify();
        $user = require_login();
        $product = (new Product())->findBySlug($slug);
        if (!$product) { http_response_code(404); echo '404 - Không tìm thấy sản phẩm'; return; }
        (new Review())->deleteMine($user['id'], (int)$product['id']);
        flash('success', 'Đã xóa đánh giá của bạn.');
        redirect('/san-pham/' . $product['slug'] . '#danh-gia');
    }
}
