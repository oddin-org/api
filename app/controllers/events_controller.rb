class EventsController < ApplicationController
  def index
    render json: Event.includes(instructions: :people).where(people: {user_id: current_user.id})
  end

  def new
    render plain: 'I display a form for creating new entity'
  end

  def create
    event = Event.new name: params[:name], code: params[:code], workload: [:workload]
    event.save!
    render json: event
  end

  def show
    render json: Event.find(params[:id])
  end

  def edit
    render plain: 'I display a form for editing an entity'
  end

  def update
    render plain: 'I update one entity'
  end

  def destroy
    render plain: 'I destroy one entity'
  end
end
