#!/usr/bin/env bash

set -euo pipefail

created_title="Milliner integration create"
updated_title="Milliner integration update"

create_output=$(docker compose exec -T drupal drush php:eval '
  $account = \Drupal\user\Entity\User::load(1);
  if ($account === null) {
    throw new \RuntimeException("The Drupal administrator account is missing");
  }
  \Drupal::currentUser()->setAccount($account);
  $models = \Drupal::entityTypeManager()
    ->getStorage("taxonomy_term")
    ->loadByProperties(["vid" => "islandora_models", "name" => "Collection"]);
  $model = reset($models);
  if ($model === false) {
    throw new \RuntimeException("The Collection Islandora model is missing");
  }
  $node = \Drupal\node\Entity\Node::create([
    "type" => "islandora_object",
    "title" => "Milliner integration create",
    "status" => 1,
    "field_model" => ["target_id" => $model->id()],
  ]);
  $node->save();
  print "MILLINER_RESULT:{$node->id()}:{$node->uuid()}\n";
')

result=$(grep -oE 'MILLINER_RESULT:[0-9]+:[0-9a-f-]+' <<<"$create_output" | tail -n 1 || true)
if [[ ! $result =~ ^MILLINER_RESULT:([0-9]+):([0-9a-f-]{36})$ ]]; then
  printf 'Could not parse the created Drupal node: %s\n' "$create_output" >&2
  exit 1
fi

node_id="${BASH_REMATCH[1]}"
uuid="${BASH_REMATCH[2]}"
pairtree="${uuid:0:2}/${uuid:2:2}/${uuid:4:2}/${uuid:6:2}/${uuid}"
fedora_url="http://localhost:8080/fcrepo/rest/${pairtree}"

fetch_fedora() {
  docker compose exec -T fcrepo sh -c '
    token=$(cat /run/secrets/JWT_ADMIN_TOKEN)
    curl --silent --show-error \
      --header "Authorization: Bearer ${token}" \
      --header "Accept: application/ld+json" \
      --write-out "\n%{http_code}" \
      "$1"
  ' sh "$fedora_url"
}

wait_for_title() {
  local expected=$1
  local response status body
  for _attempt in $(seq 1 60); do
    response=$(fetch_fedora || true)
    status=$(tail -n 1 <<<"$response" | tr -d '\r')
    body=$(sed '$d' <<<"$response")
    if [[ $status == 200 ]] && grep -Fq "$expected" <<<"$body"; then
      return 0
    fi
    sleep 5
  done
  printf 'Fedora did not contain %q at %s. Last response:\n%s\n' \
    "$expected" "$fedora_url" "$response" >&2
  return 1
}

wait_for_absence() {
  local response status
  for _attempt in $(seq 1 60); do
    response=$(fetch_fedora || true)
    status=$(tail -n 1 <<<"$response" | tr -d '\r')
    if [[ $status == 404 || $status == 410 ]]; then
      return 0
    fi
    sleep 5
  done
  printf 'Fedora resource still exists at %s. Last response:\n%s\n' \
    "$fedora_url" "$response" >&2
  return 1
}

wait_for_title "$created_title"

docker compose exec -T drupal drush php:eval "
  \$account = \\Drupal\\user\\Entity\\User::load(1);
  if (\$account === null) {
    throw new \\RuntimeException('The Drupal administrator account is missing');
  }
  \\Drupal::currentUser()->setAccount(\$account);
  \$node = \\Drupal\\node\\Entity\\Node::load(${node_id});
  if (\$node === null) {
    throw new \\RuntimeException('Integration node is missing');
  }
  \$node->setTitle('${updated_title}');
  \$node->setChangedTime(time() + 60);
  \$node->save();
"
wait_for_title "$updated_title"

docker compose exec -T drupal drush php:eval "
  \$account = \\Drupal\\user\\Entity\\User::load(1);
  if (\$account === null) {
    throw new \\RuntimeException('The Drupal administrator account is missing');
  }
  \\Drupal::currentUser()->setAccount(\$account);
  \$node = \\Drupal\\node\\Entity\\Node::load(${node_id});
  if (\$node === null) {
    throw new \\RuntimeException('Integration node is missing');
  }
  \$node->delete();
"
wait_for_absence

printf 'Verified Drupal-to-Fedora lifecycle for %s\n' "$uuid"
