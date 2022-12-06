<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthorController extends Controller
{
    public function index()
    {
        $authors = Author::all();
        return $this->getResponse200($authors);
    }

    public function store(Request $request)
    {
        $author = new Author();
        $author->name = $request->name;
        $author->first_surname = $request->first_surname;
        $author->second_surname = $request->second_surname;
        $author->save();

        return $this->getResponse201("Author", "created", $author);
    }


    public function show($id)
    {
        $author = Author::find($id);
        return $this->getResponse200($author);
    }

    public function update(Request $request, $id)
    {
        $author = Author::find($id);
        DB::beginTransaction();
        try {
            if ($author) {
                $author->name = $request->name;
                $author->first_surname = $request->first_surname;
                $author->second_surname = $request->second_surname;
                $author->update();

                $author = Author::find($id);

                return $this->getResponse201("Author", "updated", $author);
            } else {
                return $this->getResponse404();
            }
            DB::commit();
        } catch (Exception $e) {

            return $this->getResponse500($e->getMessage());

            DB::rollBack();
        }
    }

    public function destroy($id)
    {
        $author = Author::find($id);
        if ($author != null) {
            $author->delete();
            return $this->getResponseDelete200("author");
        }else{
            return $this->getResponse404();
        }
    }
}
