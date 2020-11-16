@php
    namespace App\Http\Controllers;

    use App\Helpers\General;
    use App\Mail\BeelinkReportMail;
    use Illuminate\Http\Request;

@endphp
    <!DOCTYPE html>
<html>
<head>
</head>
<body>

Date time: {{\Carbon\Carbon::today()}} <br>
Total: {{General::beelinkReport()['total']}}<br>
Successful: {{General::beelinkReport()['successful']}}<br>

<p>Thank you</p>
</body>
</html>
