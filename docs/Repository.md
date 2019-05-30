### Usage Instructions for Repository

---

#### CURD

```php
# create
$this-repository->create([
    'user_name' => 'Tony',
    'age'       => 18,
    'sex'       => 1,
    'address    => 'America'
]);
```

```php
# delete
$this->repository->delete(1); //pk = 1
$this->repository->delete(['id:gt' => 10]); // delete id > 10
``` 

```php
# update
$this->repository->update(['name:like' => '%555'], [
    'type' => 3,
    'money' => 9999
]);
```

```php
# query
# A piece of data
$this-repository->find(1);
# Multiple data
$this-repository->find(['user_id' => 10])
``` 


#### About filters`s expression
```php

# Repository.php

protected $expression = [
    // the following expression requires params string or number
    'eq'          => '=',
    'neq'         => '!=',
    'ne'          => '!=',
    'gt'          => '>',
    'egt'         => '>=',
    'gte'         => '>=',
    'ge'          => '>=',
    'lt'          => '<',
    'le'          => '<=',
    'lte'         => '<=',
    'elt'         => '<=',
    'like'        => 'LIKE',
    'not_like'    => 'NOT LIKE',
    'not like'    => 'NOT LIKE',
    
    // the following expression requires params array
    'in'          => 'In',
    'not_in'      => 'NotIn',
    'not in'      => 'NotIn',
    'between'     => 'Between',
    'not_between' => 'NotBetween',
    'not between' => 'NotBetween',
];

// you can use the expression like this:
# find the data where id > 10
$this->repository->findAll(['id:gt' => 10]);
# find the data where id != 10
$this->repository->findAll(['id:neq' => 10]);
# find the data where id in 1,2,3,4,5
$this->repository->findAll(['id:in' => [1,2,3,4,5]);
# find the data where created_at is between 
# 2019-01-01 00:00:00 and 2020-01-01 00:00:00
$this->repository->findAll(['created_at:between' => 
    [
        '2019-01-01 00:00:00', 
        '2020-01-01 00:00:00
    ]
]);
# stop the account end with @@@
$this->repository->update(['name:like' => '%@@@'], ['status' => 0]);

``` 


#### Advanced usage

```php
# Example 1:
# For example, you have an users table, 
# the user`s extension info saved in user_ext table
# maybe you want to find the users and its extension info 
# at the same time

# step 1.Determining model relationships at Users.php(model)

/**
 * @return \Illuminate\Database\Eloquent\Relations\HasOne
 */
public function extInfo()
{
    # if foreignKey == localKey, you could only write the first user_id 
    # that`s enough
    return $this->hasOne(UsersExt::class, 'user_id');
}

# step 2.do like this
$this->userRepository->findAll(
    ['status' => 1],  // filters
    [
        '*', // users columns
        'extInfo' => [
            // user_ext columns
            'address', 
            'sex', 
            'hobby', 
            'phone'
        ] 
    ]// fields
);
```
```php
# Example 2:
# the same tables, users and user_ext
# maybe you want to find the users who user_id > 10 and address is NewYork

# step 1.
# define scope in users model like this

/**
 * @param $query
 * @param $address
 * @return mixed
 */
public function scopeAddress($query, $address)
{
    return $query->leftJoin('user_ext', function ($join) use ($address) {
        $join->on('user_ext.user_id', '=', 'users.user_id');
    })->where('user_ext.address', '=', $address);
}

# step 2.
# use it like this

$users = $this->userRepository->findAll(
    ['user_id:gt' => 10, 'address' => 'NewYork']
);

```

Is it very simple? ^_^ 😜

To be continued
