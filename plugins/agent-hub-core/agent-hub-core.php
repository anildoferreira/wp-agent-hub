<?php
/**
 * Plugin Name: Agent Hub Core
 * Description: Barramento central de API REST, CORS e Manifesto de Skills/Agentes para o Google AI Studio e MCP.
 * Version: 0.1.0
 * Author: Anildo Ferreira
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', function () {
    // Libera CORS para consumo direto pelo Google AI Studio e frontends externos
    remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
    add_filter( 'rest_pre_serve_request', function ( $value ) {
        header( 'Access-Control-Allow-Origin: *' );
        header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
        header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce' );
        return $value;
    });

    // Rota publica de healthcheck
    register_rest_route( 'agentes/v1', '/health', [
        'methods'             => 'GET',
        'callback'            => function () {
            return rest_ensure_response([
                'status'    => 'ok',
                'timestamp' => gmdate( 'c' ),
                'wp_version'=> get_bloginfo( 'version' ),
            ]);
        },
        'permission_callback' => '__return_true',
    ]);

    // Rota autenticada de descoberta de Agentes e Skills ativos
    register_rest_route( 'agentes/v1', '/manifest', [
        'methods'             => 'GET',
        'callback'            => 'agent_hub_get_manifest',
        'permission_callback' => function () {
            return current_user_can( 'edit_posts' );
        },
    ]);
});

function agent_hub_get_manifest() {
    $tools = apply_filters( 'agent_hub_register_tools', [] );
    return rest_ensure_response([
        'status' => 'online',
        'count'  => count( $tools ),
        'tools'  => array_values( $tools ),
    ]);
}