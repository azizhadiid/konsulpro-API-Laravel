<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Kontak Baru dari KonsulPro</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            /* Menggunakan Inter, atau fallback sans-serif */
            line-height: 1.6;
            color: #333333;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 12px;
            /* Rounded corners */
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e0e0e0;
        }

        .header {
            background-color: #2563eb;
            /* Blue-600 */
            background-image: linear-gradient(to right, #2563eb, #4f46e5);
            /* Blue to Indigo */
            color: #ffffff;
            padding: 30px 25px;
            text-align: center;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .header p {
            margin-top: 8px;
            font-size: 16px;
            opacity: 0.9;
        }

        .content {
            padding: 25px;
            color: #4a4a4a;
        }

        .content h2 {
            color: #2563eb;
            /* Blue-600 */
            font-size: 22px;
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }

        .detail-item {
            margin-bottom: 15px;
            background-color: #f9f9f9;
            padding: 12px 15px;
            border-left: 4px solid #3b82f6;
            /* Blue-500 */
            border-radius: 8px;
        }

        .detail-item strong {
            display: block;
            font-size: 14px;
            color: #555555;
            margin-bottom: 4px;
        }

        .detail-item span {
            font-size: 16px;
            color: #333333;
        }

        .message-box {
            background-color: #f0f8ff;
            /* Light blue background */
            border: 1px solid #cceeff;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }

        .message-box p {
            margin: 0;
            font-size: 15px;
            line-height: 1.8;
            white-space: pre-wrap;
            /* Mempertahankan format baris baru */
        }

        .footer {
            text-align: center;
            padding: 25px;
            font-size: 12px;
            color: #888888;
            border-top: 1px solid #eeeeee;
            margin-top: 30px;
        }

        .footer a {
            color: #2563eb;
            text-decoration: none;
        }

        .logo {
            margin-bottom: 15px;
        }

        .logo svg {
            width: 40px;
            height: 40px;
            vertical-align: middle;
            margin-right: 10px;
        }

    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <!-- SVG Logo atau Text Logo -->
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                    style="width: 40px; height: 40px; vertical-align: middle; margin-right: 10px; color: #ffffff;">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span style="font-size: 28px; font-weight: bold; color: #ffffff;">KonsulPro</span>
            </div>
            <p>Pesan Kontak Baru</p>
        </div>
        <div class="content">
            <p>Halo Admin,</p>
            <p>Anda telah menerima pesan baru melalui formulir kontak website Anda. Berikut detailnya:</p>

            <div class="detail-item">
                <strong>Nama Pengirim:</strong>
                <span>{{ $data['name'] }}</span>
            </div>
            <div class="detail-item">
                <strong>Email Pengirim:</strong>
                <span>{{ $data['email'] }}</span>
            </div>
            <div class="detail-item">
                <strong>Subjek:</strong>
                <span>{{ $data['subject'] }}</span>
            </div>

            <div class="message-box">
                <strong>Pesan:</strong>
                <p>{{ $data['message'] }}</p>
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} KonsulPro. Semua hak dilindungi.</p>
            <p>Transforming Business Through Technology.</p>
        </div>
    </div>
</body>

</html>
