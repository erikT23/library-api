<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthorCrontroller extends Controller
{
    public function index()
    {

        $authors = Author::orderBy('name', 'asc')->get();
        return $this->getResponse200($authors);
    }

    public function store(Request $request)
    {
        try {
            $author = new Author();
            $author->name = $request->name;
            $author->first_surname = $request->first_surname;
            $author->second_surname = $request->second_surname;
            $author->save();
            return $this->getResponse201('author', 'created', $author);

        } catch (Exception $e) {
            return $this->getResponse500([$e]);
        }
    }

    public function update(Request $request, $id)
    {
        $author = Author::find($id);
        DB::beginTransaction();

        try {
            $author->name = $request->name;
            $author->first_surname = $request->first_surname;
            $author->second_surname = $request->second_surname;
            $author->update();
            DB::commit();
            return $this->getResponse201("author", "updated", $author);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->getResponse500($e);
        }
    }

    public function destroy(Request $request, $id)
    {
        $author = Author::find($id);
        try {
            if ($author) {
                foreach ($author->books as $item) {
                    $author->books()->detach($item->id);
                }
            }
            $author->delete();

            return $this->getResponseDelete200($author);
        } catch (Exception $e) {
            return $this->getResponse500([$e]);
        }

    }

}
