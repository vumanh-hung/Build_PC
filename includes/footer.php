<!-- ===== FOOTER ===== -->
<footer>
  <p>© <?= date('Y') ?> BuildPC.vn — Máy tính & Linh kiện chính hãng</p>
</footer>

<style>
  /* ===== RESET LAYOUT ===== */
  html, body {
    height: 100%;
    margin: 0;
    padding: 0;
  }

  body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    font-family: "Segoe UI", Tahoma, sans-serif;
  }

  main {
    flex: 1; /* phần nội dung chiếm không gian còn lại */
  }

  /* ===== FOOTER ===== */
  footer {
    background: linear-gradient(90deg, #007bff 0%, #00aaff 50%, #007bff 100%);
    color: white;
    text-align: center;
    padding: 24px 20px;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.3px;
    box-shadow: 0 -4px 12px rgba(0, 107, 255, 0.1);
    margin-top: auto; /* giúp footer luôn ở cuối */
  }

  footer p {
    margin: 0;
  }
</style>
