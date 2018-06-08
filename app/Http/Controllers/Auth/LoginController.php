<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';
    /**
     * @var string
     * return massage
     */
    public $msg = '';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function username()
    {
        return 'userid';
    }

    public function login(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }else{
            $un_auth = ['userid' => $request->input('userid'),'password'=>$request->input('password')];
            $is_cuc = json_decode($this->get_info_cuc('/auth/auth', 'POST', $un_auth), true);
            if($is_cuc['code'] == '20'){
                $info = $this->get_info_cuc('/auth/getInfo', 'POST', $un_auth);
                $un_auth['info'] = json_decode($info, true)['info'];
                //dump($un_auth['info']);die();
                if($request->has('email'))
                {
                    $this->validate($request,[
                        'userid'=>'required|string',
                        'password'=>'required|string',
                        'email'=>'required|string|email'
                    ]);
                }else{
                    return view('auth.register',$un_auth);
                }
                $un_auth['name'] = $un_auth['info']['name'];
                $un_auth['password'] = Hash::make($un_auth['password']);
                $un_auth['type'] = $is_cuc['role'];//1.学生 2.教师 3.管理员
                $un_auth['email'] = $request->input('email');
                $un_auth['info'] = json_encode($un_auth['info'],JSON_UNESCAPED_UNICODE);
                $res = User::updateOrCreate($un_auth);
                if($res){
                    if($this->attemptLogin($request))
                        return $this->sendLoginResponse($request);
                    $this->msg = '验证失败';
                }else{
                    $this->msg = '用户创建失败';
                }
            }else{
                $this->msg = $is_cuc['msg'];
            }
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        $this->msg = '参数错误';
        return $this->sendFailedLoginResponse($request);
    }

    public function sendFailedLoginResponse(Request $request)
    {
        return view('auth.login',[
            'userid' => $request->input('userid'),
            'msg' => $this->msg
        ]);
    }

}
