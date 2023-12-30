# Intro

Demonstrates a bug in Filament in an attempt to fix [Issue #8067](https://github.com/filamentphp/filament/issues/8067)

Bug introduced in [PR 10078](https://github.com/filamentphp/filament/pull/10078)

This bug only occurs when the parent and child tables are the same and the **pivot table has an incrementing id**.

## Installation

```bash
composer install

php artisan migrate:fresh --seed

php artisan make:filament-user
```

Create a user, sign in, and create try to attach a parent task to reproduce the error.

## The error

```
SQLSTATE[23000]: Integrity constraint violation: 1052 Column 'id' in where clause is ambiguous
```

```sql
select distinct `tasks`.*
from `tasks`
         left join `task_edges` on `tasks`.`id` = `task_edges`.`parent_id`
where not exists (select *
                  from `tasks` as `laravel_reserved_0`
                           inner join `task_edges` on `laravel_reserved_0`.`id` = `task_edges`.`child_id`
                  where `tasks`.`id` = `task_edges`.`parent_id`
                    and `id` = 1)
order by `tasks`.`name` asc limit 50
```

The error is in the `where` clause: `and `id` = 1`. The `id` column is ambiguous, it should be `laravel_reserved_0`.`id`
instead to only target the subquery. This is happening because the pivot table has an incrementing id. To solve this we
need to use the relationship table to query the id.

```php
$relationCountHash = $relationship->getRelationCountHash(false);
$relationshipQuery
    ->when(
        !$table->allowsDuplicates(),
        fn (Builder $query): Builder => $query->whereDoesntHave(
            $table->getInverseRelationship(),
            fn (Builder $query): Builder => $query->where(
                // https://github.com/filamentphp/filament/issues/8067
                $relationship->getParent()->getTable() === $relationship->getRelated()->getTable() ?
                    ($relationCountHash.'.'.$relationship->getParent()->getKeyName()) :
                    $relationship->getParent()->getQualifiedKeyName(),
                $relationship->getParent()->getKey(),
            ),
        ),
    );
```

Perhaps $relationCountHash could be renamed to $relationshipSubqueryHash or something similar to make it more clear what
it is.
