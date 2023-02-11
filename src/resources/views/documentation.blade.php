<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ config('app.name') }} - API Documentation</title>

    <script src="https://unpkg.com/@stoplight/elements/web-components.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements/styles.min.css">
</head>

<body style="height: 100vh; overflow-y: hidden">
    <elements-api apiDescriptionUrl="{{ route('apdoc.json') }}" router="hash" />
    <style>
        .sl-stack--vertical.sl-stack--8>:not(style)~:not(style) {
            margin-top: 10px !important;
        }

        .sl-stack--vertical.sl-stack--10>:not(style)~:not(style) {
            margin-top: 20px !important;
        }

        div[data-testid="two-column-right"] {
            max-width: unset !important
        }
        .auto-scrol{
            overflow: auto;
        }
        .HttpOperation__Description p{
            font-size: 15px
        }
        .table-response-detail{
            display: flex;
        }
        .table-response-detail table{
            margin: 0px !important;
        }
        .table-response-detail table:first-child{
            margin-right: 15px !important;
        }
        .table-response-detail table tr th{
            padding: 10px 15px;
            font-size: 17px
        }
        div[data-testid="two-column-right"]{
            width: 45% !important;
            margin-left: 25px !important;
        }
        .sl-overflow-y-auto{
            max-height: unset !important
        }
    </style>
</body>

</html>