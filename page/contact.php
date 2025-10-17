<?php include '../includes/header.php'; ?>
<style>
/* ==== TỔNG THỂ ==== */
.contact-section {
    background: linear-gradient(135deg, #a2c2e2, #2e8bfa);
    color: #fff;
    padding: 60px 20px;
    text-align: center;
    border-radius: 20px;
    margin-top: 40px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

/* ==== TIÊU ĐỀ ==== */
.contact-section h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 20px;
}

/* ==== FORM LIÊN HỆ ==== */
.contact-form {
    background: #fff;
    color: #333;
    border-radius: 15px;
    padding: 30px;
    max-width: 700px;
    margin: 40px auto;
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
}

.contact-form input,
.contact-form textarea {
    width: 100%;
    padding: 12px;
    margin-top: 10px;
    border: 2px solid #cce0ff;
    border-radius: 10px;
    font-size: 1rem;
    transition: 0.3s;
}

.contact-form input:focus,
.contact-form textarea:focus {
    border-color: #2e8bfa;
    outline: none;
    box-shadow: 0 0 5px #2e8bfa;
}

.contact-form button {
    background: #2e8bfa;
    color: #fff;
    border: none;
    padding: 12px 25px;
    font-size: 1.1rem;
    border-radius: 10px;
    cursor: pointer;
    transition: 0.3s;
}

.contact-form button:hover {
    background: #1c6fe2;
}

/* ==== THÔNG TIN LIÊN HỆ ==== */
.contact-info {
    margin-top: 50px;
    text-align: left;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

.contact-info h4 {
    color: #2e8bfa;
    font-weight: bold;
}

.contact-info p {
    margin-bottom: 8px;
    font-size: 1rem;
}

/* ==== BẢN ĐỒ ==== */
.map-container {
    margin-top: 40px;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
</style>

<div class="container contact-section">
    <h1>Liên Hệ Với Chúng Tôi</h1>
    <p>📞 Nếu bạn có bất kỳ thắc mắc nào về sản phẩm hoặc cần hỗ trợ kỹ thuật, hãy gửi thông tin cho chúng tôi qua form dưới đây.</p>
</div>

<div class="container contact-form">
    <form method="POST" action="">
        <div class="mb-3">
            <label for="name">Họ và Tên</label>
            <input type="text" id="name" name="name" placeholder="Nhập họ và tên của bạn" required>
        </div>
        <div class="mb-3">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Nhập địa chỉ email" required>
        </div>
        <div class="mb-3">
            <label for="message">Nội dung</label>
            <textarea id="message" name="message" rows="4" placeholder="Nhập nội dung liên hệ..." required></textarea>
        </div>
        <button type="submit">Gửi Liên Hệ</button>
    </form>
</div>

<div class="container contact-info">
    <h4>🏢 Văn phòng chính:</h4>
    <p>123 Đường Lê Lợi, Quận 1, TP. Hồ Chí Minh</p>

    <h4>📧 Email:</h4>
    <p>support@buildpc.vn</p>

    <h4>📞 Hotline:</h4>
    <p>0909 123 456</p>
</div>

<div class="container map-container">
    <iframe 
        src="https://www.google.com/maps?q=ho%20chi%20minh&output=embed"
        width="100%" height="400" style="border:0;" allowfullscreen loading="lazy">
    </iframe>
</div>

<?php include '../includes/footer.php'; ?>
