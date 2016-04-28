<?php

namespace App\Moldova\Repositories\Contracts;


use App\Moldova\Entities\Contracts;
use App\Moldova\Entities\OcdsRelease;

class ContractsRepository implements ContractsRepositoryInterface
{
    /**
     * @var Contracts
     */
    private $contracts;
    /**
     * @var OcdsRelease
     */
    private $ocdsRelease;

    /**
     * ContractsRepository constructor.
     * @param Contracts   $contracts
     * @param OcdsRelease $ocdsRelease
     */
    public function __construct(Contracts $contracts, OcdsRelease $ocdsRelease)
    {
        $this->contracts   = $contracts;
        $this->ocdsRelease = $ocdsRelease;
    }

    /**
     * {@inheritdoc}
     */
    public function getContractsByOpenYear()
    {
        $result = Contracts::raw(function ($collection) {
            return $collection->find([], [
                    "contractDate" => 1,
                    "_id"          => 1
                ]
            );
        });

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getContractorsByOpenYear()
    {
        $result = OcdsRelease::raw(function ($collection) {
            return $collection->find([], [
                    "buyer" => 1
                ]
            );
        });

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcuringAgency($type, $limit, $condition, $column)
    {
        $query  = [];
        $filter = [];

        if ($condition !== '') {
            $filter = [
                '$match' => [
                    $column => $condition
                ]
            ];
        }

        if (!empty($filter)) {
            array_push($query, $filter);
        }

        $groupBy = [
            '$group' => [
                '_id'    => '$tender.stateOrg.orgName',
                'count'  => ['$sum' => 1],
                'amount' => ['$sum' => '$amount']
            ]
        ];

        array_push($query, $groupBy);
        $sort = ['$sort' => [$type => - 1]];
        array_push($query, $sort);
        $limit = ['$limit' => $limit];
        array_push($query, $limit);

        $result = Contracts::raw(function ($collection) use ($query) {
            return $collection->aggregate($query);
        });

        return ($result);
    }

    /**
     * {@inheritdoc}
     */
    public function getContractors($type, $limit, $condition, $column)
    {
        $query  = [];
        $filter = [];

        if ($condition !== '') {
            $filter = [
                '$match' => [
                    $column => $condition
                ]
            ];
        }

        if (!empty($filter)) {
            array_push($query, $filter);
        }

        $groupBy =
            [
                '$group' => [
                    '_id'    => '$participant.fullName',
                    'count'  => ['$sum' => 1],
                    'amount' => ['$sum' => '$amount']
                ]
            ];

        array_push($query, $groupBy);
        $sort = ['$sort' => [$type => - 1]];
        array_push($query, $sort);
        $limit = ['$limit' => $limit];
        array_push($query, $limit);

        $result = Contracts::raw(function ($collection) use ($query) {
            return $collection->aggregate($query);
        });

        return ($result);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalContractAmount()
    {
        return $this->contracts->sum('amount');
    }

    /**
     * {@inheritdoc}
     */
    public function getGoodsAndServices($type, $limit, $condition, $column)
    {
        $query  = [];
        $filter = [];

        if ($condition !== '') {
            $filter = [
                '$match' => [
                    $column => $condition
                ]
            ];
        }

        if (!empty($filter)) {
            array_push($query, $filter);
        }

        $groupBy =
            [
                '$group' => [
                    '_id'    => '$goods.mdValue',
                    'count'  => ['$sum' => 1],
                    'amount' => ['$sum' => '$amount']
                ]
            ];

        array_push($query, $groupBy);
        $sort = ['$sort' => [$type => - 1]];
        array_push($query, $sort);
        $limit = ['$limit' => $limit];
        array_push($query, $limit);

        $result = Contracts::raw(function ($collection) use ($query) {
            return $collection->aggregate($query);
        });

        return ($result);
    }

    /**
     * {@inheritdoc}
     */
    public function getContractsList($params)
    {
        $orderIndex = $params['order'][0]['column'];
        $ordDir     = $params['order'][0]['dir'];
        $column     = $params['columns'][$orderIndex]['data'];
        $startFrom  = $params['start'];
        $ordDir     = (strtolower($ordDir) == 'asc') ? 1 : - 1;
        $search     = $params['search']['value'];

        return $this->contracts
            ->select(['id', 'contractNumber', 'contractDate', 'status.mdValue', 'finalDate', 'amount', 'goods.mdValue'])
            ->where(function ($query) use ($search) {

                if (!empty($search)) {
                    return $query->where('goods.mdValue', 'like', '%' . $search . '%');
                }

                return $query;
            })
            ->take($params['length'])
            ->skip($startFrom)
            ->orderBy($column, $ordDir)
            ->get();
    }

    public function getContractorsList($params)
    {
        $orderIndex  = $params['order'][0]['column'];
        $ordDir      = $params['order'][0]['dir'];
        $column      = $params['columns'][$orderIndex]['data'];
        $startFrom   = $params['start'];
        $ordDir      = (strtolower($ordDir) == 'asc') ? 1 : - 1;
        $search      = $params['search']['value'];
        $limitResult = $params['length'];

        $query  = [];
        $filter = [];

        $unwind = [
            '$unwind' => '$award'
        ];
        array_push($query, $unwind);

        if ($search != '') {
            $filter = [
                '$match' => ['award.suppliers.name' => $search]
            ];
        }

        if (!empty($filter)) {
            array_push($query, $filter);
        }

        $groupBy =
            [
                '$group' => [
                    '_id'    => '$award.suppliers.name',
                    'count'  => ['$sum' => 1],
                    'scheme' => ['$addToSet' => '$award.suppliers.additionalIdentifiers.scheme'],
                ]
            ];

        array_push($query, $groupBy);
        $sort = ['$sort' => [$column => $ordDir]];
        array_push($query, $sort);
        $skip = ['$skip' => (int) $startFrom];
        array_push($query, $skip);
        $limit = ['$limit' => (int) $limitResult];
        array_push($query, $limit);

        $result = OcdsRelease::raw(function ($collection) use ($query) {
            return $collection->aggregate($query);
        });

        return ($result['result']);
    }

    /**
     * {@inheritdoc}
     */
    public function getDetailInfo($parameter, $column)
    {

        return $this->contracts
            ->select(['id', 'contractNumber', 'contractDate', 'status.mdValue', 'finalDate', 'amount', 'goods.mdValue', 'participant.fullName', 'tender.stateOrg.orgName'])
            ->where($column, '=', $parameter)
            ->get();
//        $result = Contracts::raw(function ($collection) use ($parameter, $column) {
//
//            return $collection->find(
//                [$column => $parameter],
//                [
//                    "contractNumber"          => 1,
//                    "id"                      => 1,
//                    "contractDate"            => 1,
//                    "finalDate"               => 1,
//                    "amount"                  => 1,
//                    "status.mdValue"          => 1,
//                    "goods.mdValue"           => 1,
//                    "participant.fullName"    => 1,
//                    "tender.stateOrg.orgName" => 1
//
//                ]);
//        });
//
//        return ($result);
    }

    /**a
     * {@inheritdoc}
     */
    public function getContractDetailById($contractId)
    {
        $result = $this->ocdsRelease->where('contract.id', (int) $contractId)->project(['contract.$' => 1, 'award' => 1, 'tender.id' => 1, 'tender.title' => 1, 'buyer.name' => 1])->first();

        $contract                    = ($result['contract'][0]);
        $contract['tender_title']    = $result['tender']['title'];
        $contract['tender_id']       = $result['tender']['id'];
        $contract['procuringAgency'] = $result['buyer']['name'];

        foreach ($result['award'] as $award) {
            if ($award['id'] === $contract['awardID']) {
                $contract['goods']      = (!empty($award['items']))?$award['items'][0]['classification']['description']:"-";
                $contract['contractor'] = (!empty($award['suppliers']))?$award['suppliers'][0]['name']:"-";
                break;
            }
        }
//dd( $contract);
        return $contract;
    }

    /**
     * {@inheritdoc}
     */
    public function search($search)
    {
        //dd($search);
        $q          = (!empty($search['q'])) ? $search['q'] : '';
        $contractor = (!empty($search['contractor'])) ? $search['contractor'] : '';
        $agency     = (!empty($search['agency'])) ? $search['agency'] : '';
        $range      = (!empty($search['amount'])) ? explode("-", $search['amount']) : '';

        return ($this->contracts
            ->select(['id', 'contractNumber', 'contractDate', 'finalDate', 'amount', 'goods . mdValue', 'status . mdValue'])
            ->where(function ($query) use ($q, $contractor, $range, $agency) {

                if (!empty($q)) {
                    $query->where('goods.mdValue', 'like', ' % ' . $q . ' % ')
                          ->orWhere('participant.fullName', 'like', ' % ' . $q . ' % ')
                          ->orWhere('tender.stateOrg.orgName', 'like', ' % ' . $q . ' % ');
                }
                if (!empty($contractor)) {
                    $query->where('participant . fullName', "=", $contractor);
                }

                if (!empty($agency)) {
                    $query->where('tender.stateOrg.orgName', "=", $agency);
                }

                if (!empty($search['amount']) && $range[1] != 'Above') {

                    $query->whereBetween('amount', $range);
                }


                return $query;
            })->get());
    }

    public function getAllContractTitle()
    {
        $groupBy =
            [
                '$group' => [
                    '_id'   => '$participant.fullName',
                    'count' => ['$sum' => 1]
                ]
            ];


        $result = Contracts::raw(function ($collection) use ($groupBy) {
            return $collection->aggregate($groupBy);
        });

        return ($result['result']);

    }

    /**
     * @param $contractId
     * @return mixed
     */
    public function getContractDataForJson($contractId)
    {
        return $this->ocdsRelease->where('contract.id', (int) $contractId)->project(['contract.$' => 1, 'award' => 1, 'tender.id' => 1, 'tender.title' => 1, 'buyer' => 1])->first();
    }
}
