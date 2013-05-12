-- Looks like we need queries selecting unique ids of the complete (potentially 
-- paginating) index, left outer joined to all the other required index tables for 
-- the query

select distinct p.id
	from datetime_published as p
		left outer join tagged as t using(id)
    left outer join mentioning as m using(id)
	order by p.published desc

-- We can then add where clauses for any of the index tables

-- Select all personal- or food-themed notes mentioning waterpigs.co.uk
select distinct p.id
	from datetime_published as p
		left outer join tagged as t using(id)
		left outer join mentioning as m using (id)
	where t.tag in ('personal', 'food')
	and m.url = 'waterpigs.co.uk'
order by p.published desc
