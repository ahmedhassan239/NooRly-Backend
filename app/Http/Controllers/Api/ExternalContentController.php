<?php

namespace App\Http\Controllers\Api;

use App\Domain\Hadith\Models\HadithItem;
use App\Domain\Quran\Models\QuranAyah;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExternalContentController extends Controller
{
    public function searchQuran(Request $request)
    {
        $term = $request->input('term');
        $lang = $request->input('lang', 'ar');
        $surah = $request->input('surah');

        if (!$term && !$surah) {
            return response()->json(['error' => 'Term or Surah required'], 400);
        }

        $query = QuranAyah::query();

        if ($surah) {
            // Check if user passed ID or Number. If number, we need to map to ID, but usually ID=Number for surahs.
            $query->where('surah_id', $surah);
        }

        if ($term) {
            if ($lang === 'en') {
                $query->searchEnglish($term);
            } else {
                $query->searchArabic($term);
            }
        }

        return response()->json($query->paginate(20));
    }

    public function searchHadith(Request $request)
    {
        $term = $request->input('term');
        $lang = $request->input('lang', 'ar');
        $collection = $request->input('collection');

        if (!$term && !$collection) {
            return response()->json(['error' => 'Term or Collection required'], 400);
        }

        $query = HadithItem::query();

        if ($collection) {
            $query->where('source', $collection);
        }

        if ($term) {
            if ($lang === 'en') {
                $query->searchEnglish($term);
            } else {
                $query->searchArabic($term);
            }
        }

        return response()->json($query->paginate(20));
    }
}
