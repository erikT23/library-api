<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookDownloads;
use App\Models\Book_reviews;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{
    public function index()
    {

        $books = Book::orderBy('title', 'asc')->get();
        return $this->getResponse200($books);
    }

    public function store(Request $request)
    {
        try {
            $isbn = preg_replace('/\s+/', '\u0020', $request->isbn); //Remove blank spaces from ISBN
            $existIsbn = Book::where("isbn", $isbn)->exists(); //Check if a registered book exists (duplicate ISBN)
            if (!$existIsbn) { //ISBN not registered
                $book = new Book();
                $book->isbn = $isbn;
                $book->title = $request->title;
                $book->description = $request->description;
                $book->published_date = Carbon::now();
                $book->category_id = $request->category["id"];
                $book->editorial_id = $request->editorial["id"];
                $book->save();
                $bookDownload = new BookDownloads();
                $bookDownload->book_id = $book->id;
                $bookDownload->save();
                foreach ($request->authors as $item) { //Associate authors to book (N:M relationship)
                    $book->authors()->attach($item);
                }
                $book = Book::with('bookDownload', 'category', 'editorial', 'authors')->where("id", $book->id)->get();
                return $this->getResponse201('book', 'created', $book);
            } else {
                return $this->getResponse500(['The isbn field must be unique']);
            }
        } catch (Exception $e) {
            return $this->getResponse500([]);
        }
    }

    public function update(Request $request, $id)
    {
        $book = Book::find($id);
        DB::beginTransaction();
        try {
            if ($book) {
                $isbn = trim($request->isbn);
                $Myisbn = Book::where("isbn", $isbn)->first();
                if (!$Myisbn || $Myisbn->id == $book->id) {
                    $book->isbn = $isbn;
                    $book->title = $request->title;
                    $book->description = $request->description;
                    $book->published_date = Carbon::now();
                    $book->category_id = $request->category["id"];
                    $book->editorial_id = $request->editorial["id"];
                    $book->update();
                    foreach ($book->authors as $item) { //Associate authors to book (N:M relationship)
                        $book->authors()->detach($item->id);
                    }
                    foreach ($request->authors as $item) { //Associate authors to book (N:M relationship)
                        $book->authors()->attach($item);
                    }
                    $book = Book::with('bookDownload', 'category', 'editorial', 'authors')->where("id", $id)->get();
                    DB::commit();
                    return $this->getResponse201('book', 'updated', $book);

                } else {
                    return $this->getResponse500(['ISBN duplicated!']);
                }
            } else {

                return $this->getResponse500(['Not found']);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->getResponse500(['Rollback transaction']);

        }
    }

    public function show(Request $request, $id)
    {
        $book = Book::find($id);
        try {
            if ($book) {
                $book = Book::with('category', 'editorial', 'authors')->where("id", $id)->get();
                return $this->getResponse200($book);
            } else {
                return $this->getResponse404();
            }

        } catch (Exception $e) {
            return $this->getResponse500([$e->getMessage()]);
        }

    }
    public function destroy(Request $request, $id)
    {
        $book = Book::find($id);
        try {
            if ($book) {
                foreach ($book->authors as $item) { //Associate authors to book (N:M relationship)
                    $book->authors()->detach($item->id);
                }
                $book->bookDownload()->delete();
                $book->delete();
                return $this->getResponseDelete200("book");
            } else {
                return $this->getResponse404();
            }

        } catch (Exception $e) {
            return $this->getResponse500([$e->getMessage()]);
        }

    }
    public function addBookReview(Request $request, $book_id)
    {
        $edited1 = false;
        $validator = Validator::make($request->all(), [
            'comment' => 'required',
        ]);
        if (!$validator->fails()) {
            DB::beginTransaction();
            try {
                $user = auth()->user();
                $bookRev = new Book_reviews();
                $bookRev->comment = $request->comment;
                $bookRev->edited = $edited1;
                $bookRev->book_id = $book_id;
                $bookRev->user_id = $user->id;

                $bookRev->save();
                DB::commit();
                return $this->getResponse201('book review', 'created', $bookRev);
            } catch (Exception $e) {
                DB::rollBack();
                return $this->getResponse500([$e->getMessage()]);
            }
        } else {
            return $this->getResponse500([$validator->errors()]);
        }
    }
    public function updateBookReview(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required',
        ]);
        if (!$validator->fails()) {
            DB::beginTransaction();
            try {
                $user = auth()->user();
                $bookRev = Book_reviews::where('id', $id)->get()->first();
                if (!$bookRev) {
                    return $this->getResponse404();
                } else {
                    if ($bookRev->user_id != $user->id) {
                        return $this->getResponse403();
                    } else {
                        $bookRev->comment = $request->comment;
                        $bookRev->edited = true;
                        $bookRev->update();
                        DB::commit();
                        return $this->getResponse201('book review', 'updated', $bookRev);
                    }
                }

            } catch (Exception $e) {
                DB::rollBack();
                return $this->getResponse500([$e->getMessage()]);
            }
        } else {
            return $this->getResponse500([$validator->errors()]);
        }
    }

}
