<?php include '../includes/header.php'; ?>
<style>
/* ==== PHẦN NỀN VÀ BỐ CỤC ==== */
.about-section {
    background: linear-gradient(135deg, #a2c2e2, #2e8bfa);
    color: #fff;
    padding: 80px 20px;
    text-align: center;
    border-radius: 20px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

/* ==== TIÊU ĐỀ ==== */
.about-section h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 20px;
    letter-spacing: 1px;
}

/* ==== ĐOẠN VĂN ==== */
.about-section p {
    font-size: 1.1rem;
    line-height: 1.8;
    max-width: 800px;
    margin: 0 auto 30px auto;
}

/* ==== KHUNG GIỚI THIỆU NHÓM ==== */
.team {
    margin-top: 50px;
}
.team h2 {
    color: #2e8bfa;
    margin-bottom: 30px;
}
.team-member {
    background: #fff;
    color: #333;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}
.team-member:hover {
    transform: translateY(-5px);
}
.team-member h4 {
    color: #2e8bfa;
    font-weight: bold;
}
</style>

<div class="container about-section mt-5">
    <h1>Về Chúng Tôi</h1>
    <p>
        💻 <strong>BuildPC</strong> là nền tảng hỗ trợ người dùng dễ dàng lựa chọn, cấu hình và mua sắm linh kiện máy tính phù hợp nhất.  
        Với giao diện thân thiện, thông tin minh bạch và tính năng so sánh linh kiện thông minh — chúng tôi giúp bạn tự tin tạo nên bộ PC mạnh mẽ, tối ưu hiệu năng và chi phí.
    </p>
    <p>
        Sứ mệnh của chúng tôi là mang đến cho người dùng trải nghiệm mua sắm linh kiện trực tuyến <strong>nhanh chóng - chính xác - chuyên nghiệp</strong>.  
        Mỗi sản phẩm được chọn lọc kỹ càng từ các thương hiệu uy tín hàng đầu như <em>ASUS, MSI, GIGABYTE, Intel, AMD</em>...
    </p>
</div>

<div class="container team text-center">
    <h2>👨‍💻 Nhóm Phát Triển</h2>
    <div class="row justify-content-center">
        <div class="col-md-3 team-member mx-3 mb-4">
            <h4>Nguyễn Xuân Minh</h4>
            <p> Backend Developer</p>
        </div>
        <div class="col-md-3 team-member mx-3 mb-4">
            <h4>Vũ Mạnh Hùng</h4>
            <p>Admin</p>
        </div>
        <div class="col-md-3 team-member mx-3 mb-4">
            <h4>Phúc Huy</h4>
            <p>Database </p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
