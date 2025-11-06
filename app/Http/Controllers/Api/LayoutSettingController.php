<?php
// app/Http/Controllers/Api/SliderSettingController.php
namespace App\Http\Controllers;

use App\Models\LayoutSetting;
use Illuminate\Http\Request;

class LayoutSettingController extends Controller
{
    public function getSettings()
    {
        $settings = LayoutSetting::first();
        if (!$settings) {
            $settings = LayoutSetting::create(); // default
        }
        return response()->json($settings);
    }

    public function updateSettings(Request $request)
    {
        $settings = LayoutSetting::first() ?? new LayoutSetting();
        $settings->update($request->all());
        return response()->json(['message' => 'Settings updated', 'data' => $settings]);
    }
}

