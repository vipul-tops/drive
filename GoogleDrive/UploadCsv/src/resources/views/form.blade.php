<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload File to Google Drive</title>
    
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>

    <div class="card">
        <div class="card-body">
            <div class="container mt-5">
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                <br>
                @if($token && $now->lessThan($token->expires_at))
                    {{-- $token->access_token--}}
                    <div class="card">
                        <div class="card-header">Direct Upload CSV File In Google Drive</div>
                        <div class="card-body">
                            <!-- <form>
                                <div class="form-group">
                                    <label for="exampleInputPassword1">Model Name</label>
                                    <input type="password" class="form-control" id="exampleInputPassword1" placeholder="Password">
                                </div>
                                <div class="form-group">
                                    <label for="column">Column</label>
                                    <input type="text" class="form-control" id="column" placeholder="Password">
                                </div>
                            </form> -->
                            <!-- <a href="{{ url('/uploadlargefile') }}" class="btn btn-success"> Upload CSV File </a> -->

                            <a href="https://drive.google.com/drive/u/0/my-drive" class="btn btn-secondary float-right">Google Drive</a>

                        </div>
                    </div>
                    <!-- <a href="/logout" class="btn btn-danger mt-3">Logout</a> -->
                @else
                    <div class="card">
                        <div class="card-header">Upload File</div>
                        <div class="card-body">
                            <a href="{{url ('/refreshtoken') }}" class="btn btn-primary">Sign in with Google</a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
