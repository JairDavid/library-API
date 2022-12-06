<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookReview;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{

    public function index()
    {
        // $books = Book::all();
        $books = Book::with("bookDownload", "category", "editorial", "authors")->get();
        return $this->getResponse200($books);
    }

    public function store(Request $request)
    {
        $isbn = trim($request->isbn);
        $existsIsbn = Book::where("isbn", $isbn)->exists();
        if (!$existsIsbn) {
            $book = new Book();
            $book->isbn = $isbn;
            $book->title = $request->title;
            $book->description = $request->description;
            $book->published_date = Carbon::now();
            $book->category_id = $request->category['id'];
            $book->editorial_id = $request->editorial['id'];
            $book->save();

            foreach ($request->authors as $item) {
                $book->authors()->attach($item);
            }

            return $this->getResponse201("Book", "created", $book);
        } else {
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
                $isbnOwner = Book::where("isbn", $isbn)->first();

                if (!$isbnOwner || $isbnOwner->id == $book->id) {
                    $book->isbn = $isbn;
                    $book->title = $request->title;
                    $book->description = $request->description;
                    $book->published_date = Carbon::now();
                    $book->category_id = $request->category['id'];
                    $book->editorial_id = $request->editorial['id'];
                    $book->update();

                    foreach ($book->authors as $item) {
                        $book->authors()->detach($item->id);
                    }

                    foreach ($request->authors as $item) {
                        $book->authors()->attach($item);
                    }

                    $book = Book::with('category', 'editorial', 'authors')->where("id", $id)->get();

                    return $this->getResponse201("Book", "updated", $book);
                } else {
                    return $this->getResponse500([]);
                }
            } else {
                return $this->getResponse404();
            }
            DB::commit();
        } catch (Exception $e) {

            return $this->getResponse500([$e->getMessage()]);

            DB::rollBack();
        }
    }


    public function show($id)
    {
        $book = Book::with("bookDownload", 'category', 'editorial', 'authors')->where("id", $id)->get();
        return $this->getResponse200($book);
    }


    public function destroy($id)
    {
        $book = Book::find($id);
        foreach ($book->authors as $item) {
            $book->authors()->detach($item->id);
        }
        $book->delete();
        return $this->getResponseDelete200("book");
    }

    public function addBookReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required'
        ]);
        if (!$validator->fails()) {
            DB::beginTransaction();
            try {
                $user = $request->user();
                $book_review = new BookReview();
                $book_review->comment = $request->comment;
                $book_review->edited = false;
                $book_review->book_id = $request->book_id;
                $book_review->user_id = $user->id;

                $book_review->save();
                DB::commit();
                return $this->getResponse201('book review', 'created', $book_review);
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
        $book_review = BookReview::find($id);
        DB::beginTransaction();
        try {
            if ($book_review) {
                $user_id = $request->user()->id;

                if ($user_id == $book_review->id) {
                    $book_review->comment = $request->comment;
                    $book_review->edited = true;
                    $book_review->save();

                    $book_review = BookReview::with('book', 'user')->where("id", $id)->get();

                    return $this->getResponse201("book review", "updated", $book_review);
                } else {
                    return $this->getResponse403();
                }
            } else {
                return $this->getResponse404();
            }
            DB::commit();
        } catch (Exception $e) {
            return $this->getResponse500([$e->getMessage()]);
            DB::rollBack();
        }
    }
}
