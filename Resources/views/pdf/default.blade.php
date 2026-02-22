<!DOCTYPE html>
<meta charset="UTF-8">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    @section('css')
        {!! csscrush_inline(public_path('Projects/'.config('app.project').'/css/pdf_web.css')) !!}
    @show
</head>
<body>
@section('content')
@show
</body>
</html>
