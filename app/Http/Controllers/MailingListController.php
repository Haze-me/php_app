<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToMailingList;
use App\Models\MailingList;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MailingListController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/mailing-list",
     * operationId="mailingList",
     * tags={"WaitingList"},
     * summary="Waiting List",
     * description="Waiting List Here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"name", "email"},
     *               @OA\Property(property="name", type="text"),
     *               @OA\Property(property="email", type="email")
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Successfully Joined the Waiting List",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Successfully Joined the Waiting List",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function store(AddToMailingList $request)
    {
        $request->validated($request);

        $addmail = MailingList::create([
            'fullName' => $request->name,
            'email' => $request->email,
            'created_at' => Carbon::now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'You have successfully been added to our waiting list!',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): Response
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): Response
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        //
    }
}
