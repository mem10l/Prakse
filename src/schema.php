<?php

declare(strict_types=1);

const TABLES = [
    'categories' => [
        'columns'  => ['categoryid', 'categoryname', 'description'],
        'required' => ['categoryname'],
    ],
    'products' => [
        'columns'  => ['productid', 'productname', 'supplierid', 'categoryid',
                       'quantityperunit', 'unitprice', 'unitsinstock', 'unitsonorder',
                       'reorderlevel', 'discontinued'],
        'required' => ['productname'],
    ],
    'customers' => [
        'columns'  => ['customerid', 'companyname', 'contactname', 'contacttitle',
                       'address', 'city', 'region', 'postalcode', 'country', 'phone', 'fax'],
        'required' => ['customerid', 'companyname'],
    ],
    'suppliers' => [
        'columns'  => ['supplierid', 'companyname', 'contactname', 'contacttitle',
                       'address', 'city', 'region', 'postalcode', 'country', 'phone', 'fax', 'homepage'],
        'required' => ['companyname'],
    ],
    'orders' => [
        'columns'  => ['orderid', 'customerid', 'employeeid', 'orderdate', 'requireddate',
                       'shippeddate', 'shipvia', 'freight', 'shipname', 'shipaddress',
                       'shipcity', 'shipregion', 'shippostalcode', 'shipcountry'],
        'required' => ['customerid'],
    ],
    'order_details' => [
        'columns'  => ['orderid', 'productid', 'unitprice', 'quantity', 'discount'],
        'required' => ['orderid', 'productid', 'quantity'],
    ],
    'employees' => [
        'columns'  => ['employeeid', 'lastname', 'firstname', 'title', 'titleofcourtesy',
                       'birthdate', 'hiredate', 'address', 'city', 'region', 'postalcode',
                       'country', 'homephone', 'extension', 'notes', 'reportsto'],
        'required' => ['lastname', 'firstname'],
    ],
    'shippers' => [
        'columns'  => ['shipperid', 'companyname', 'phone'],
        'required' => ['companyname'],
    ],
    'region' => [
        'columns'  => ['regionid', 'regiondescription'],
        'required' => ['regionid', 'regiondescription'],
    ],
    'territories' => [
        'columns'  => ['territoryid', 'territorydescription', 'regionid'],
        'required' => ['territoryid', 'territorydescription', 'regionid'],
    ],
    'employeeterritories' => [
        'columns'  => ['employeeid', 'territoryid'],
        'required' => ['employeeid', 'territoryid'],
    ],
    'customercustomerdemo' => [
        'columns'  => ['customerid', 'customertypeid'],
        'required' => ['customerid', 'customertypeid'],
    ],
    'customerdemographics' => [
        'columns'  => ['customertypeid', 'customerdesc'],
        'required' => ['customertypeid'],
    ],
];
