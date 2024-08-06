<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Response\Message;
use Illuminate\Http\Request;
use App\Functions\GlobalFunction;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\DisplayRequest;
use App\Http\Resources\LoginResource;
use App\Http\Requests\User\StoreRequest;
use App\Http\Requests\User\Login\LoginRequest;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\User\ChangePasswordRequest;

class UserController extends Controller
{
    public function index(DisplayRequest $request)
    {
        $status = $request->status;

        $user = User::with("role")
            ->when("inactive" === $status, function ($query) {
                $query->onlyTrashed();
            })
            ->useFilters()
            ->orderByDesc("updated_at")
            ->dynamicPaginate();

        $is_empty = $user->isEmpty();

        if ($is_empty) {
            return GlobalFunction::notFound();
        }

        UserResource::collection($user);
        return GlobalFunction::responseFunction(Message::USER_DISPLAY, $user);
    }
    public function store(StoreRequest $request)
    {
        $user = User::create([
            "prefix_id" => $request->prefix_id,
            "id_number" => $request->id_number,
            "first_name" => $request->first_name,
            "middle_name" => $request->middle_name,
            "last_name" => $request->last_name,
            "suffix" => $request->suffix,
            "position_name" => $request->position_name,
            "mobile_no" => $request->mobile_no,
            "company_id" => $request->company_id,
            "business_unit_id" => $request->business_unit_id,
            "department_id" => $request->department_id,
            "department_unit_id" => $request->department_unit_id,
            "sub_unit_id" => $request->sub_unit_id,
            "location_id" => $request->location_id,
            "warehouse_id" => $request->warehouse_id,
            "username" => $request->username,
            "password" => Hash::make($request->username),
            "role_id" => $request->role_id,
        ]);

        $user_collect = new UserResource($user);

        return GlobalFunction::save(Message::REGISTERED, $user_collect);
    }
    public function update(StoreRequest $request, $id)
    {
        $user_id = Auth()->user()->id;

        $user = User::find($id);

        if ($user_id === $user->id) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $user->update([
            "prefix_id" => $request->prefix_id,
            "id_number" => $request->id_number,
            "middle_name" => $request->middle_name,
            "last_name" => $request->last_name,
            "suffix" => $request->suffix,
            "position_name" => $request->position_name,
            "mobile_no" => $request->mobile_no,
            "company_id" => $request->company_id,
            "business_unit_id" => $request->business_unit_id,
            "department_id" => $request->department_id,
            "department_unit_id" => $request->department_unit_id,
            "sub_unit_id" => $request->sub_unit_id,
            "location_id" => $request->location_id,
            "warehouse_id" => $request->warehouse_id,
            "username" => $request->username,
            "role_id" => $request->role_id,
        ]);
        return GlobalFunction::responseFunction(Message::USER_UPDATE, $user);
    }
    public function destroy($id)
    {
        $user_auth = Auth()->user()->id;

        $user = User::where("id", $id)
            ->withTrashed()
            ->get();

        $user_id = User::where("id", $id)
            ->withTrashed()
            ->get()
            ->first();

        if ($user_auth === $user_id->id) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        if ($user->isEmpty()) {
            return GlobalFunction::notFound(Message::NOT_FOUND);
        }

        $user = User::withTrashed()->find($id);
        $is_active = User::withTrashed()
            ->where("id", $id)
            ->first();

        if (!$is_active) {
            return $is_active;
        } elseif (!$is_active->deleted_at) {
            $user->delete();
            $message = Message::ARCHIVE_STATUS;
        } else {
            $user->restore();
            $message = Message::RESTORE_STATUS;
        }
        return GlobalFunction::responseFunction($message, $user);
    }
    public function login(LoginRequest $request)
    {
        $user = User::with("role")
            ->where("username", $request->username)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                "username" => ["The provided credentials are incorrect."],
                "password" => ["The provided credentials are incorrect."],
            ]);

            if ($user || Hash::check($request->password, $user->username)) {
                return GlobalFunction::invalid(Message::INVALID_ACTION);
            }
        }
        $token = $user->createToken("PersonalAccessToken")->plainTextToken;
        $user["token"] = $token;

        $cookie = cookie("project-ymir", $token);

        $user = new LoginResource($user);

        return GlobalFunction::responseFunction(
            Message::LOGIN_USER,
            $user
        )->withCookie($cookie);
    }
    public function logout(Request $request)
    {
        Auth()
            ->user()
            ->currentAccessToken()
            ->delete();
        return GlobalFunction::responseFunction(Message::LOGOUT_USER);
    }
    public function resetPassword($id)
    {
        $user = User::find($id);

        if (!$user) {
            return GlobalFunction::invalid(Message::INVALID_ACTION);
        }

        $user->update([
            "password" => Hash::make($user->username),
        ]);
        return GlobalFunction::responseFunction(Message::CHANGE_PASSWORD);
    }
    public function changePassword(ChangePasswordRequest $request)
    {
        $id = Auth::id();
        $user = User::find($id);

        if ($user->username == $request->password) {
            throw ValidationException::withMessages([
                "password" => ["Please change your password."],
            ]);
        }

        $user->update([
            "password" => Hash::make($request["password"]),
        ]);
        return GlobalFunction::responseFunction(Message::CHANGE_PASSWORD);
    }
}
