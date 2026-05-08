<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #f4f4f5; margin: 0; padding: 40px 20px; color: #18181b; }
    .card { background: #fff; border-radius: 12px; max-width: 600px; margin: 0 auto; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    .header { background: linear-gradient(135deg, #1e40af, #3b82f6); padding: 32px; color: #fff; }
    .header h1 { margin: 0 0 4px; font-size: 20px; font-weight: 700; }
    .header p  { margin: 0; font-size: 13px; opacity: .8; }
    .body { padding: 32px; }
    .field { margin-bottom: 24px; }
    .label { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; }
    .value { font-size: 15px; color: #111827; line-height: 1.6; }
    .message-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; font-size: 14px; line-height: 1.8; white-space: pre-wrap; }
    .footer { padding: 20px 32px; background: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af; }
  </style>
</head>
<body>
  <div class="card">
    <div class="header">
      <h1>お問い合わせを受信しました</h1>
      <p>予約・配車管理システム サポートデスク</p>
    </div>
    <div class="body">
      <div class="field">
        <div class="label">お名前</div>
        <div class="value">{{ $senderName }}</div>
      </div>
      <div class="field">
        <div class="label">メールアドレス</div>
        <div class="value">{{ $senderEmail }}</div>
      </div>
      <div class="field">
        <div class="label">カテゴリ</div>
        <div class="value">{{ $category }}</div>
      </div>
      <div class="field">
        <div class="label">お問い合わせ内容</div>
        <div class="message-box">{{ $body }}</div>
      </div>
      <div class="field" style="margin-bottom:0">
        <div class="label">受信日時</div>
        <div class="value">{{ now()->timezone('Asia/Tokyo')->format('Y年m月d日 H:i') }}</div>
      </div>
    </div>
    <div class="footer">
      このメールは予約・配車管理システムから自動送信されました。
    </div>
  </div>
</body>
</html>
