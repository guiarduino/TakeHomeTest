<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;

class AccountController extends Controller
{

    // Metodo para resetar tudo o que foi feito
    public function reset(Request $request)
    {
        $request->session()->put('accounts', [
            300 => new Account(300, 0),
        ]);

        return response()->json([
            "message" => 'OK'
        ], 200);
    }
    
    // Esse metodo retorna o saldo da conta caso ela exista
    public function find(Request $request)
    {
        $account_id = $request->query('account_id');

        if(array_key_exists($account_id, $request->session()->get('accounts')))
        {
            $account = $request->session()->get('accounts')[$account_id];
            return response($account->balance, 200);
        }else
        {
            return response(0, 404);
        }
    }

    /*
        Metodo para tratar os eventos, são tratados de acordo com o parametro 'type'
    */
    public function event(Request $request)
    {
        switch ($request->get('type'))
        {
            // Fazer um deposito em uma conta existente ou criar uma nova conta
            case 'deposit':
                $response = $this->deposit($request);
                return $response;

                break;
            case 'withdraw':
                $response = $this->withdraw($request);
                return $response;

                break;
            case 'transfer':
                $response = $this->transfer($request);
                return $response;

                break;


        }

    }

    // Metodo para depoistar um valor ao saldo do usuario
    public function deposit(Request $request)
    {
        // Validar os dados da requisição
        $validated = $request->validate([
            'type' => 'required|string',
            'destination' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        //Verificar se conta existe
        $account = $this->getAccount($validated['destination']);

        // Se encontrar a Account acrescenta saldo
        if($account)
        {
            $account = $request->session()->get('accounts')[$validated['destination']];
            $account->balance += $validated['amount'];
        } else { // Caso Account nao seja encontrada Salva nova Conta
            $accounts = $request->session()->get('accounts');
            $account = new Account($validated['destination'], $validated['amount']);
            $accounts[$validated['destination']] = $account;
            $request->session()->put('accounts', $accounts);
        }

        return response([
            'destination' => [
                'id' => $account->id,
                'balance' => $account->balance
            ]], 201);

    }

    //Metodo para savar um valor da conta se existente
    public function withdraw(Request $request)
    {
        // Validar os dados da requisição
        $validated = $request->validate([
            'type' => 'required|string',
            'destination' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        //Verificar se conta existe
        $account = $this->getAccount($validated['destination']);

        // Se encontrar a Account decresce o saldo
        if($account)
        {
            $account = $request->session()->get('accounts')[$validated['destination']];

            // Testar se a quantidade estipulada pode ser sacada
            if($account->balance - $validated['amount'] >= 0)
            {
                $account->balance -= $validated['amount'];
                
                return response([
                    'destination' => [
                        'id' => $account->id,
                        'balance' => $account->balance
                    ]], 201);

            }else{
                return response('Insufficient Balance!', 422);
            }

        } else { // Caso Account nao seja encontrada retorna 404
            return response(0, 404);
        }

    }

    // Esse metodo funciona para fazer transferencias entre duas contas existentes
    public function transfer(Request $request)
    {
        // Validar os dados da requisição
        $validated = $request->validate([
            'type' => 'required|string',
            'destination' => 'required|string',
            'amount' => 'required|numeric',
            'origin' => 'required|string',
        ]);

        //Verificar se as contas existem
        $destination = $this->getAccount($validated['destination']);
        $origin = $this->getAccount($validated['origin']);

        // Se encontrar as Accounts realiza a transferencia
        if($destination && $origin)
        {
            $account_destination = $request->session()->get('accounts')[$validated['destination']];
            $account_origin = $request->session()->get('accounts')[$validated['origin']];

            // Testar se a quantidade estipulada pode ser transferida
            if($account_origin->balance - $validated['amount'] >= 0)
            {
                $account_origin->balance -= $validated['amount'];
                $account_destination->balance += $validated['amount'];

                return response([
                    'origin' => [
                        'id' => $account_origin->id,
                        'balance' => $account_origin->balance
                    ],
                    'destination' => [
                        'id' => $account_destination->id,
                        'balance' => $account_destination->balance
                    ]], 201);

            }else{
                return response('Insufficient Balance!', 422);
            }

        } else { // Caso Account nao seja encontrada retorna 404
            return response(0, 404);
        }
    }

    // metodo para pegar a Account por id
    public function getAccount(int $id_account)
    {
        $account = (array_key_exists($id_account, session()->get('accounts'))) ? session()->get('accounts')[$id_account] : false;
        return $account;
    }
    
}