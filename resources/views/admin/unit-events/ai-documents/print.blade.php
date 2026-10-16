<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $aiDocument->title }}</title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0;
    padding: 24px;
    font-family: "Times New Roman", Times, serif;
    background: #f3f4f6;
    color: #0f172a;
    font-size: 12pt;
    line-height: 1.5;
  }
  .no-print {
    max-width: 800px;
    margin: 0 auto 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .no-print a,
  .no-print button {
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #0f172a;
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
  }
  .no-print a:hover,
  .no-print button:hover {
    background: #f8fafc;
  }
  .sheet {
    max-width: 800px;
    margin: 0 auto;
    background: #fff;
    padding: 40px 50px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    min-height: 297mm;
  }
  
  /* Text and element layouts */
  h1, h2, h3, h4 {
    font-family: Arial, sans-serif;
    color: #000;
    margin-top: 0;
  }
  h1 { font-size: 18pt; font-weight: bold; border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 20px; }
  h2 { font-size: 14pt; margin-top: 24px; margin-bottom: 10px; }
  h3 { font-size: 12pt; margin-top: 18px; margin-bottom: 8px; }
  p { margin-bottom: 12px; text-align: justify; text-indent: 0.5in; }
  p:first-of-type { text-indent: 0; }
  
  table {
    border-collapse: collapse;
    width: 100%;
    margin: 16px 0;
  }
  th, td {
    border: 1px solid #000;
    padding: 8px 10px;
    text-align: left;
  }
  th {
    background-color: #f1f5f9;
    font-weight: bold;
  }

  /* Print specific overrides */
  @media print {
    body {
      background: none;
      padding: 0;
    }
    .no-print {
      display: none;
    }
    .sheet {
      box-shadow: none;
      margin: 0;
      padding: 0;
      width: 100%;
      min-height: auto;
    }
  }
</style>
</head>
<body>

<div class="no-print">
  <a href="{{ route('admin.unit-events.ai-documents.index', [$unitPage, $unitEvent, 'document_id' => $aiDocument->id]) }}">
    ← Kembali ke Editor
  </a>
  <button onclick="window.print()">
    🖨️ Cetak Dokumen
  </button>
</div>

<div class="sheet">
  <h1>{{ $aiDocument->title }}</h1>
  <div>
    {!! $aiDocument->content_html !!}
  </div>
</div>

<script>
  // Trigger print dialog on load
  window.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
      window.print();
    }, 500);
  });
</script>

</body>
</html>
